begin;
alter table signer add receive_updates boolean not null default true;
commit;
