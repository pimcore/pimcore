/*
PostgreSQL supports multiline comments from SQL standard
/*
Including nested ones
insert into foo (bar) values ('bar value');
*/
delete from foo;
*/

-- # is not a comment in PostgreSQL, but a legitimate operator
select 1
# 2;

-- // can also be an operator in PostgreSQL
-- http://www.postgresql.org/docs/current/static/sql-syntax-lexical.html#SQL-SYNTAX-OPERATORS
select 'foo'
// 'bar';

insert into foo (bar, "strange;name""indeed") values ('bar''s value containing ;', 'a value for strange named column');

-- dollar quoting (example from docs)
create function foo(text)
returns boolean as
$function$
BEGIN
    RETURN ($1 ~ $q$[\t\r\n\v\\]$q$);
END;
$function$
language plpgsql;

-- straight from http://www.phing.info/trac/attachment/ticket/499
CREATE FUNCTION phingPDOtest() RETURNS "trigger"
    AS $_X$
if (1)
{
    # All is well - just continue
    return;
}
else
{
    # Not good - this is probably a fatal error!
    elog(ERROR,"True is not true");
    return "SKIP";
}
$_X$
    LANGUAGE plperl;

insert into foo (bar) -- I can be safely ignored as PostgreSQL does not have hints
values ('${bar.value}');
insert into foo (bar) values ($$ a dollar-quoted string containing a few quotes ' ", a $placeholder$ and a semicolon;$$);

-- "create rule" statement may contain semicolons inside parentheses
create rule blah_insert
as on insert to blah do instead (
    insert into foo values (new.id, 'blah');
    insert into bar values (new.id, 'blah-blah');
);


insert into dump (message) values ('I am a statement not ending with a delimiter')