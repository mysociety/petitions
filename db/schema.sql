-- 
-- schema.sql:
-- Schema for petitions database.
--
-- Copyright (c) 2006 UK Citizens Online Democracy. All rights reserved.
-- Email: francis@mysociety.org; WWW: http://www.mysociety.org/
--
-- $Id: schema.sql,v 1.68 2010-04-22 15:58:55 matthew Exp $
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

-- information about things that receive petitions
create table body (
    id integer not null primary key default nextval('global_seq'),
    area_id integer,
    ref text not null, -- short name for URLs
    name text not null
);

-- information about each petition
create table petition (
    id integer not null primary key default nextval('global_seq'),
    -- short name of petition for URLs
    ref text not null,
    -- Body this petition is applied to
    body_id integer references body(id),

    -- "We the undersigned petition the Prime Minister to..."
    content text not null default '', -- LLL
    -- more details of petition
    detail text not null, -- LLL
    -- target deadline, midnight at end of this day
    deadline date not null,
    -- actual text entered, just in case parse_date() goes wrong
    rawdeadline text not null,
    comments text not null,
    category integer not null default 0,

    -- petition creator
    email text not null,
    name text not null,
    organisation text not null,
    address text not null,
    postcode text,
    overseas text,
    telephone text not null,
    org_url text not null,
    address_type text not null default '' check (
        address_type in (
            'home',
            'work',
            'study',
            ''
        )
    ),

    -- metadata
    creationtime timestamp not null,
    cached_signers integer not null default 1,

    -- Offline petition component
    offline_signers integer,
    offline_link text,
    offline_location text,

    -- Optional person/thing responsible for this petition
    responsible text,

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
    archived timestamp,

    -- the _categories fields are bitmasks of possible reasons in
    -- petition.php; the constraints here must be kept up to date
    -- with that list of reasons.
    rejection_first_categories integer not null default 0
        check (rejection_first_categories >= 0
                and rejection_first_categories < 262144),
    rejection_first_reason text,
    rejection_second_categories integer not null default 0
        check (rejection_second_categories >= 0
                and rejection_second_categories < 262144),
    rejection_second_reason text,
    rejection_hidden_parts integer not null default 0
        check (rejection_hidden_parts >= 0
                and rejection_hidden_parts < 64),

    laststatuschange timestamp not null default ms_current_timestamp(),
    lastupdate timestamp not null default ms_current_timestamp()

    -- add fields to run confirmation email stuff

    check ((rejection_first_categories = 0
                and rejection_first_reason is null)
            or (rejection_first_categories <> 0
                and rejection_first_reason is not null)),

    check ((rejection_second_categories = 0
                and rejection_second_reason is null)
            or (rejection_second_categories <> 0
                and rejection_second_reason is not null)),
    check (
        (postcode is not null and overseas is null)
        or (postcode is null and overseas is not null)
    )

);
ALTER TABLE petition CLUSTER ON petition_pkey;

create unique index petition_ref_idx on petition(ref);
create unique index petition_lower_ref_idx on petition(lower(ref));
create index petition_status_idx on petition(status);
create index petition_category_idx on petition(category);
create index petition_laststatuschange_idx on petition(laststatuschange);
create index petition_lastupdate_idx on petition(lastupdate);
create index petition_deadline_idx on petition(deadline);
create index petition_cached_signers_idx on petition(cached_signers);
create index petition_cached_signers_status_deadline_idx on petition(cached_signers, status, deadline); -- Not sure about this one, but it's currently there

create table petition_area (
    petition_id integer not null references petition(id) on delete cascade,
    area_id integer not null
);
create unique index petition_area_petition_id_area_id_idx on petition_area(petition_id, area_id);
create index petition_area_id_idx on petition_area(area_id);

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
    postcode text,
    address_type text not null default '' check (
        address_type in (
            'home',
            'work',
            'study',
            ''
        )
    ),
    latitude float,
    longitude float,
    overseas text,
    receive_updates boolean not null default true,

    -- whether this signer is included in the petition or not
    -- (should really be called "notdeleted" or something)
    showname boolean not null default false, -- XXX this column is confusingly named

    -- when they signed
    signtime timestamp not null,

    -- has the confirmation mail been sent to the user, and have they
    -- clicked the confirm link?
    emailsent text not null default ('pending') check (
        emailsent in (
        'pending',          -- not sent yet
        'sent',             -- successfully sent
        'failed',           -- permanent failure
        'confirmed',        -- confirm link clicked
        'duplicate'         -- has signed petition again
        )
    ),
    check (
        (postcode is not null and overseas is null)
        or (postcode is null and overseas is not null)
    )
);
ALTER TABLE signer CLUSTER ON signer_pkey;

create index signer_petition_id_idx on signer(petition_id);
create unique index signer_petition_id_email_idx on signer(petition_id, lower(email)) where email != '';
create index signer_emailsent_idx on signer(emailsent);
create index signer_showname_idx on signer(showname);
create index signer_petition_id_emailsent_showname on signer(petition_id, emailsent, showname);
create index signer_emailsent_showname_idx on signer(emailsent, showname);
create index signer_signtime_idx on signer(signtime);
create index signer_email_idx on signer(lower(email)) where email != '';

create table signer_area (
    signer_id integer not null references signer(id) on delete cascade,
    area_id integer not null
);
create unique index signer_area_signer_id_area_id_idx on signer_area(signer_id, area_id);
create index signer_area_id_idx on signer_area(area_id);

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

create table message (
    id integer not null primary key default nextval('global_seq'),
    petition_id integer not null references petition(id),
    circumstance text not null,
    circumstance_count int not null default 0,
    whencreated timestamp not null default ms_current_timestamp(),
    fromaddress text not null default 'admin'
        check (fromaddress in ('admin', 'admin-html', 'creator')),

    -- who should receive it
    sendtoadmin boolean not null,
    sendtocreator boolean not null,
    sendtosigners boolean not null,
    sendtolatesigners boolean not null,
    -- content of message
    emailtemplatename text,
    emailtemplatevars text,
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
create index message_circumstance_idx on message(circumstance);
create index message_sendtoadmin_idx on message(sendtoadmin);

-- To whom have messages been sent?
create table message_admin_recipient (
    message_id integer not null references message(id)
);
create index message_admin_recipient_message_id_idx on message_admin_recipient(message_id);

create table message_creator_recipient (
    message_id integer not null references message(id),
    petition_id integer not null references petition(id)
);

create unique index message_creator_recipient_message_id_petition_id_idx
    on message_creator_recipient(message_id, petition_id);

create table message_signer_recipient (
    message_id integer not null references message(id),
    signer_id integer not null references signer(id) on delete cascade
);

create unique index message_signer_recipient_message_id_signer_id_idx
    on message_signer_recipient(message_id, signer_id);

-- Table for people who don't want to receive government responses
create table optout (
    id integer not null primary key default nextval('global_seq'),
    email text not null
);
create unique index optout_email_idx on optout(lower(email));

-- Statistics that are too slow to make for admin page
create table stats (
    id integer not null primary key default nextval('global_seq'),
    whencounted timestamp not null,
    key text not null,
    value text not null
);
create index stats_key_idx on stats(key);

