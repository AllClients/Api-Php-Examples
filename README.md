# Using the AllClients API with PHP
The API requires every request send the Account ID and API Key, which can be obtained by logging into your account and accessing the [API Settings] page.

## API Requests
Every request must be HTTP [POST] type. The examples use [cURL] for communicating with the API, which is widely available on production PHP servers. If you are using a PHP framework or CMS there is likely a wrapper that simplifies and strengthens this part. If cURL is not available on the server or a better technology _is_ available, a function like WordPress's [wp_remote_post] or library like [Guzzle] remove all the hassle by picking the best compatible communications layer and doing all of the configuration, all while reducing the amount of code you need to write.

## XML Responses
With few exceptions the AllClients API returns responses as XML. The examples use [SimpleXML] to parse the responses. SimpleXML has been enabled by default since PHP 5.1.2, and provides a simpler interface for demo purposes than [DOMDocument]. DOMDocument is more powerful but also much more verbose. It is an implementation of the [W3C DOM API](http://www.w3.org/DOM/), which can be advantageous for programmers coming from other languages.

## Datetime Strings
The `$format` in the following brief example as used with the [date_create_from_format] function will parse the datetime strings to a PHP [DateTime] object:
``` php
    $format    = "n/j/Y g:i:s a";
    $input     = "2/15/2014 1:15:05 PM";
    $time_zone = new DateTimeZone("America/Los_Angeles");
    $date      = DateTime::createFromFormat($format, $input, $time_zone);
    
    print $date->format($date::ISO8601);
    /* Prints: 2014-02-15T13:15:05-0800 */
```
>_The [GetContacts Example](#getcontacts-example) includes working code to parse API datetime strings to PHP [DateTime] objects._

### Time Zone
It is important to note that PHP will use the current time zone if not specified (see `$time_zone` above). Time zone and daylight savings time observance are [customizable within AllClients](http://kb.allclients.com/m/6807/l/323248-customize-your-time-zone-and-daylight-savings-time) and should be specified to match settings when parsing. Refer to the complete [List of Supported Timezones](http://php.net/manual/en/timezones.php) in PHP. For daylight savings time observance in PHP: *America/Denver* is Mountain Time Zone and *America/Pheonix* is Mountain with *no DST*; *America/Adak* is Hawaii, and *Pacific/Honolulu* is Hawaii with *no DST*.

# Basic Examples
The account ID and API key must be set in the first lines of each of the examples in order to run them. They can be run from the command line or from a web browser, and each exemplify the use of a single API method.

## 'AddContact' example
[sample/basic-add-contact.php](sample/basic-add-contact.php) uses the AddContact API method to create a new contact and return the contact ID.

## 'GetContacts' example
[sample/basic-get-contacts.php](sample/basic-get-contacts.php) uses the GetContacts API method to retrieve and list the contacts on an account.

## New Contact Form example
[sample/new-contact.php](sample/new-contact.php) must be run in a web browser, and contains an HTML form to create a new contact with the AddContact method. A list of flags and to-do plans are also shown and, when selected, are added to the new contact using the ContactFlags and AssignToDoPlan methods respectively. If you have PHP 5.4+ installed on your system, you can download or clone this repository and use the [built-in web server](http://php.net/manual/en/features.commandline.webserver.php):
``` sh
    $ cd allclients-php-examples
    $ php -S localhost:8000
```
And then load http://localhost:8000/sample/new-contact.php in your browser.

[curl]: http://php.net/manual/en/book.curl.php
[api settings]: http://www.allclients.com/ApiKey.aspx
[simplexml]: http://php.net/manual/en/simplexml.examples-basic.php
[domdocument]: http://php.net/manual/en/class.domdocument.php
[guzzle]: https://github.com/guzzle/guzzle
[post]: http://en.wikipedia.org/wiki/POST_%28HTTP%29
[date_create_from_format]: http://php.net/manual/en/datetime.createfromformat.php
[datetime]: http://php.net/manual/en/class.datetime.php
[wp_remote_post]: http://codex.wordpress.org/Function_Reference/wp_remote_post