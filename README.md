https://travis-ci.org/DoSomething/mbp-user-import.svg?branch=master

mbp-user-import
===============

Message Broker - Producer - User import from CVS sources that creates queue entries in the `userImportQueue`.

The work flow of the user import process is:
- **mbp-user-import_manageData.php** : Gathers CSV user files from gmail account "machines@dosomething.org".
- **mbp-user-import** : Processes CSV files to generate queue entries in `userImportQueue`.
- **mbc-user-import.php** : Consumes `userImportQueue` to trigger processing in various systems within the Message Broker system. Details of which functionality is triggered based on the `source` of the user data is explained in the mbc-user-import repository: https://github.com/DoSomething/mbc-user-import.

Installation
----------
**Production**
- `$ composer install`
**Development**
- `*composer install --no-dev`
