-- 
-- schema.sql:
-- Schema for petitions database.
--
-- Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
-- Email: francis@mysociety.org; WWW: http://www.mysociety.org/
--
-- $Id: schema.sql,v 1.31 2006-10-05 23:02:18 matthew Exp $
--

-- global_seq
-- Global sequence counter.
create sequence global_seq;

-- secret
-- A random secret.
create table secret (
    secret text not null
);

-- If a row is present, that is date which is "today".  Used for debugging
-- to advance time without having to wait.
create table debugdate (
    override_today date
);

-- Returns the date of "today", which can be overriden for testing.
create function ms_current_date()
    returns date as '
    declare
        today date;
    begin
        today = (select override_today from debugdate);
        if today is not null then
           return today;
        else
           return current_date;
        end if;

    end;
' language 'plpgsql';

-- Returns the timestamp of current time, but with possibly overriden "today".
create function ms_current_timestamp()
    returns timestamp as '
    declare
        today date;
    begin
        today = (select override_today from debugdate);
        if today is not null then
           return today + current_time;
        else
           return current_timestamp;
        end if;
    end;
' language 'plpgsql';

-- information about each petition
create table petition (
    id integer not null primary key default nextval('global_seq'),
    -- short name of petition for URLs
    ref text not null,

    -- "We the undersigned petition the Prime Minister to..."
    content text not null default '', -- LLL
    -- more details of petition
    detail text not null, -- LLL
    -- target deadline, midnight at end of this day
    deadline date not null,
    -- actual text entered, just in case parse_date() goes wrong
    rawdeadline text not null,
    comments text not null,

    -- petition creator
    email text not null,
    name text not null,
    organisation text not null,
    address text not null,
    postcode text not null,
    telephone text not null,
    org_url text not null,

    -- metadata
    creationtime timestamp not null,
    
    status text not null default 'unconfirmed' check (
        status in (
        'unconfirmed',      -- email not yet confirmed nor confirmation sent
        'failedconfirm',    -- confirmation email delivery failed
        'sentconfirm',      -- confirmation email sent
        'draft',            -- waiting for approval
        'rejectedonce',     -- rejected once
        'resubmitted',      -- resubmitted
        'rejected',         -- rejected finally, or timed out
        'live',             -- active
        'finished'          -- deadline passed
        )
    ),

    -- the _categories fields are bitmasks of possible reasons in
    -- admin-pet.php; the constraints here must be kept up to date
    -- with that list of reasons.
    rejection_first_categories integer not null default 0
        check (rejection_first_categories >= 0
                and rejection_first_categories < 4096),
    rejection_first_reason text,
    rejection_second_categories integer not null default 0
        check (rejection_second_categories >= 0
                and rejection_second_categories < 4096),
    rejection_second_reason text,

    laststatuschange timestamp not null

    -- add fields to run confirmation email stuff

    check ((rejection_first_categories = 0
                and rejection_first_reason is null)
            or (rejection_first_categories <> 0
                and rejection_first_reason is not null)),

    check ((rejection_second_categories = 0
                and rejection_second_reason is null)
            or (rejection_second_categories <> 0
                and rejection_second_reason is not null))
);

create unique index petition_ref_idx on petition(ref);
create index petition_status_idx on petition(status);

-- History of things which have happened to a petition
create table petition_log (
    order_id integer not null primary key default nextval('global_seq'), -- for ordering
    petition_id integer not null references petition(id),
    whenlogged timestamp not null,
    message text not null,
    editor text -- administrator who performed this action, or NULL
);

create table signer (
    id integer not null primary key default nextval('global_seq'),
    petition_id integer not null references petition(id),

    -- Who has signed the petition.
    email text not null,
    name text not null,
    address text not null,
    postcode text not null,

    -- whether this signer is included in the petition or not
    showname boolean not null default false,
      
    -- when they signed
    signtime timestamp not null,

    -- has the confirmation mail been sent to the user, and have they
    -- clicked the confirm link?
    emailsent text not null default ('pending') check (
        emailsent in (
        'pending',          -- not sent yet
        'sent',             -- successfully sent
        'failed',           -- permanent failure
        'confirmed'         -- confirm link clicked
        )
    )
);

create index signer_petition_id_idx on signer(petition_id);
create unique index signer_petition_id_email_idx on signer(petition_id, email);
create index signer_emailsent_idx on signer(emailsent);

-- petition_is_valid_to_sign PETITION EMAIL
-- Check whether the PETITION is valid for EMAIL to sign.
-- Returns one of:
--      ok          petition is OK to sign
--      none        no such petition exists
--      finished    petition has expired
--      signed      signer has already signed this petition
create function petition_is_valid_to_sign(integer, text)
    returns text as '
    declare
        p record;
    begin
        select into p *
            from petition
            where petition.id = $1;

        if not found then
            return ''none'';
        end if;

        -- check for signed before finished, so repeat sign-ups by same
        -- person give the best message
        if $2 = p.email then
            return ''signed'';
        end if;
        perform signer.id from signer
            where petition_id = $1
                and signer.email = $2;
        if found then
            return ''signed'';
        end if;

        if p.deadline < ms_current_date() then
            return ''finished'';
        end if;
        
        return ''ok'';
    end;
    ' language 'plpgsql';

-- petition_last_change_time PETITION
-- Return the time of the last change to PETITION.
create function petition_last_change_time(integer)
    returns timestamp as '
    declare
        t timestamp;
        t2 timestamp;
    begin
        t := (select creationtime from petition where id = $1);
--        t2 := (select changetime from petition where id = $1);
--        if t2 > t then
--            t = t2;
--        end if;
        t2 := (select signtime from signer where petition_id = $1 order by signtime desc limit 1);
        if t2 > t then
            t = t2;
        end if;
        return t;
    end;
' language 'plpgsql';

create table message (
    id integer not null primary key default nextval('global_seq'),
    petition_id integer not null references petition(id),
    circumstance text not null,
    circumstance_count int not null default 0,
    whencreated timestamp not null default ms_current_timestamp(),
    fromaddress text not null default 'number10'
        check (fromaddress in ('number10', 'creator')),

    -- who should receive it
    sendtoadmin boolean not null,
    sendtocreator boolean not null,
    sendtosigners boolean not null,
    sendtolatesigners boolean not null,
    -- content of message
    emailtemplatename text,
    emailsubject text, -- LLL
    emailbody text, -- LLL

    check (
        -- Raw email message
        (emailbody is not null and emailsubject is not null
            and emailtemplatename is null)
        -- Templated email message
        or (emailtemplatename is not null
            and emailsubject is null and emailbody is null)
    )
);

create unique index message_petition_id_circumstance_idx
    on message(petition_id, circumstance, circumstance_count);

-- To whom have messages been sent?
create table message_admin_recipient (
    message_id integer not null references message(id)
);

create table message_creator_recipient (
    message_id integer not null references message(id),
    petition_id integer not null references petition(id)
);

create unique index message_creator_recipient_message_id_petition_id_idx
    on message_creator_recipient(message_id, petition_id);

create table message_signer_recipient (
    message_id integer not null references message(id),
    signer_id integer not null references signer(id)
);

create unique index message_signer_recipient_message_id_signer_id_idx
    on message_signer_recipient(message_id, signer_id);

