PipeSupport
===========

An email "pipe" based support system written in PHP with multiple "agent" support.

Agent are assigned via email, and their addresses are masked when they respond to a ticket. All tickets/responses are forwarded to all representatives.

PipeSupport will run on any Unix system with a mail server supporting mail piping, for instance Postfix.

### cPanel Configuration

Upload the script outside your public_html. Change the file permissions of `pipe.php` to `0755`. 

Now, in cPanel, click the *Add Forwarder* button. In the *Address To Forward*, type `|php -q /home/yourname/pipesupport/pipe.php`. Click *Add Forwarder*.

### Postfix Configuration

To install this on Postfix (preconfigured by yourself), find and open /etc/aliases - add the line
`pipesupport: "|/var/pipesupport/pipe.php"`

Then edit your "virtual" database, perhaps /etc/postfix/virtual, or MySQL, depending on your setup.

Add a record for support@yourdomain.com, with the target `pipesupport`.


### Editing config.php

config.php not only contains the configuration settings, but also fallback if one does not have the pecl_http extension installed. 

When editing config.php, the only requirements are to set up your (MySQL/whatever) by inserting the correct PDO configuration.

For example, 
```php
$_DATABASE = array(
    "type" => "mysql",
    "host" => "localhost",
    "username" => "root",
    "password" => "",
    "database" => "pipesupport"
);
```
A database need only be created, on the first run of the pipe, the table will automatically be created and populated.

If you really want to, you can run the SQL query yourself:
```sql
CREATE TABLE IF NOT EXISTS `support_tickets` (
        `ticket_id` INT NOT NULL AUTO_INCREMENT,
        PRIMARY KEY(`ticket_id`),
        `sender` VARCHAR(255),
        `cc` TEXT,
        `update` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) AUTO_INCREMENT=10000000;
```

You should configure the email to send from with the `$_MAIN_EMAIL` variable, and add all the emails of your representatives to `$_STAFF`

For instance:
```php
$_STAFF = array(
  "you@example.com",
  "jamie@r.cx"
);
$_MAIN_EMAIL = "support@yourdomain.com";
```

