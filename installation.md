# Installing Petitions software

If you're going to install Petitions on your own server, we recommend you do
so on a system running Debian squeeze if possible; if not, some flavour of
Ubuntu. Of course, other operating systems (even non-Unix ones) ought to be
possible, but we don't have any direct experience of installing petitions on
those.

## Manual installation

To set up Petitions yourself, proceed with the instructions below.

Broadly speaking, you need git, Perl 5.8, php5, a webserver (such as apache)
and a database. We strongly recommend you use PostgreSQL: it's possible that
you can use another database, but if you do you will almost certainly need to
change some of the SQL. (Note: if you do successfully run with another database, be sure to let us know!)

Petitions also runs daemons and cronjobs (see note at the end of this document).

### 1. Get the code

Fetch the latest version from GitHub:

    mkdir Petitions
    cd Petitions
    git clone --recursive https://github.com/mysociety/petitions.git
    cd petitions

(if you're running an old version of git, prior to 1.6.5, you'll have to clone
and then run `git submodule update --init` separately).

We encourage you run your petitions site out of a git clone like this, because
it means you'll be able to benefit from future changes (bug fixes, new
features) that we make to the repository. If you look into the code you'll see
that we handle customisation using a "cobrand" mechanism that lets all
petitions sites run off the same core so you can update to future versions of
the code without breaking your own installation.

### 2. Create a new PostgreSQL database

Petitions uses a PostgreSQL database, so install PostgreSQL first (e.g.
`apt-get install postgresql-8.4` on Debian, or install from the PostgreSQL
website).

The default settings assume the database is called `pet` and the user is also
called `pet`. You can change these if you like. Using the defaults, create a
user and database using the following:

    $ sudo su -c psql postgres
    postgres=# CREATE USER pet WITH PASSWORD 'somepassword';
    CREATE ROLE
    postgres=# CREATE DATABASE pet WITH OWNER pet;
    CREATE DATABASE
    postgres=# \c pet
    You are now connected to database "pet".
    pet=# CREATE LANGUAGE plpgsql;
    CREATE LANGUAGE
    pet=# \q
    $

You should be able to connect to the database with `psql -U pet pet` -- if
not, you will need to investigate how to allow access to your PostgreSQL
database.

Now you can use the sql in `db/schema.sql` to create the required tables,
functions, and so on. For example, run:

    $ psql -h localhost -U pet pet < db/schema.sql

### 3. Install prerequisite packages

#### On Debian / Linux

If you're using Debian 6.0 ("squeeze") then the packages to install some
required dependencies are listed in `conf/packages`. To install all of them
you can run:

    sudo xargs -a conf/packages apt-get install

A similar list of packages should work for other Debian-based distributions.
(Please let us know if you would like to contribute such a package list or
instructions for other distributions.)

### 5. Set up general config

The settings for Petitions are defined in `conf/general`. This file does not
exist in the repository -- because it contains your own settings -- but there
is a sample file `conf/general-example` which contains many defaults. Copy
this into place with

    cp conf/general-example conf/general

and edit the settings. You *must* edit some of the settings to make your
Petitions installation work. For example, you'll need to set the database
password for the user you created earlier.

The `conf/general` file contains explanations and examples of the settings
that you can provide.

### 6. Set up webserver (httpd) config

Some necessary Apache webserver configuration is in `conf/httpd.conf-example`.
You need to put this file somewhere where your Apache webserver will read it.
There's more than one way of doing this: we usually copy it to
`conf/httpd.conf` and then add an `Include` directive in our webserver's main
config file, but you may prefer to add the contents of this file directly into
your own `httpd.conf`. This really does depend on how your system currently
runs.

If you look inside the example file, you'll see it's mostly `RewriteRule`
declarations that make nice URLs -- the site won't work properly without these
because the pages Petitions generates assumes the URLs are of this form.

If you're not running an Apache webserver, you'll need to translate the
contents of `httpd.conf-example` into the appropriate flavour of
configuration.

Note that you may have to enable some Apache modules (for example,
`mod_rewrite`) to make the `httpd.conf` work. These may already be enabled, so
check your local installation to find out.


### Restrict access to the admin

The Petitions admin interface is found at your `http://www.example.com/admin`.
The Petitions code does not restrict access to this -- use a mechanism such as
htauth (username and password) to control this. On mySociety servers, we
prevent any direct access, and proxy the `/admin` URL through our secure
`https` server.


### Daemons

There are two scripts that should be run as daemons:

   * `bin/petmaild` (the daemon that handles the outgoing email queue)

   * `bin/petsignupd` (the daemon that handles incoming signatures)

These tasks aren't simply handled by the webserver because petitions sites --
especially on a national scale -- can have very heavy peak traffic.

See `conf/petemaild-debian.ugly` and `conf/petsignupd-debian.ugly` for
template files to put into your `/etc/init.d` directory.

### Cron jobs

Petitions uses cron jobs for a few non-urgent tasks, such as updating admin
stats and so on. You *can* run Petitions without enabling these (just run the
commands manually from time to time), but ideally you should automate this
with `cron`.

Tasks that are suitable for regularly running are:

   * `bin/send-messages`
   * `bin/update-areas`
   * `bin/response-send`
   * `bin/update-stats`
   * `bin/remove-old-data` (weekly)
   
If you want to see graph showing the progress of petitions in the admin:

   * `bin/petition-signup-graph`
   * `bin/petition-creation-graph`

There is an example crontab in `conf/crontab.ugly` that implements this. At
the moment this is in the "ugly" format used by mySociety's internal
deployment tools. To convert this to a valid crontab the following should be
done -- copy the file to somewhere else, and:

* Replace `!!(*= $user *)!!` with the name of the user the cron should run under
* Replace `!!(* $vhost *)!!` with the path to the Petitions code.



### More information

The Petitions code is at https://github.com/mysociety/petitions

Contact us at http://www.mysociety.org/contact


