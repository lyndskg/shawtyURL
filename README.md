# shawtyURL

<p align="center">
<img width="530" alt="URL Shortener" src="https://miro.medium.com/max/830/1*Pdw7h5X6vQQNVopIzHBG6A.jpeg"> 
</p>

## Overview 

<ins>__Language__</ins>: PHP

<ins>__Completed on__</ins>: February 3rd, 2023

This is a simple URL shortener built for PHP that takes in a URL query parameter in order to generate a shortened URL. The URL path to be condensed is first converted into an integer ID, then padded and encoded using an MD5 hash &mdash; which can also be calculated with an optional salt value for potentially improved security.

URLs are further saved in a SQL database (aptly named `database.sql`!) and retrieved via the [PDO extension](https://www.simplilearn.com/tutorials/php-tutorial/pdo-in-php) &mdash; a lightweight, consistent framework for accessing databases in PHP. Moreover, `HTML` responses are generated for all saved URLs, but the response format can be altered to support `XML`, `.txt`, and `JSON` as well.

Implementation is loosely based on Mike Cao's [Shorty](https://github.com/mikecao/shorty) installation.


## To Do

This project is fully implemented, but lacks proper commenting. It also needs to be tested and debugged more extensively.

## Usage

After configuring your webserver (*e.g.* <b>Apache</b> or <b>Nginx</b>) and editing `config.php` to set up your preferences and environment, simply pass in a URL query parameter to the installation:

```
http://example.com/?url=http://www.google.com
```

This will return a shortened URL such as the following:

```
http://example.com/9xq
```

## Whitelist

By default, anyone is allowed to enter a new URL for shortening. 

To restrict the saving of URLs to only certain IP addresses, use the `allow` function:

```
$ shortener->allow('192.168.0.10');
```
