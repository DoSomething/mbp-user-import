### User Import System

- [Introduction](https://github.com/DoSomething/mbp-user-import/wiki)
- [Architecture](https://github.com/DoSomething/mbp-user-import/wiki/2.-Architecture)
- [Setup](https://github.com/DoSomething/mbp-user-import/wiki/3.-Setup)
- [Operation](https://github.com/DoSomething/mbp-user-import/wiki/4.-Operation)
- [Monitoring](https://github.com/DoSomething/mbp-user-import/wiki/5.-Monitoring)
- [Problems / Solutions](https://github.com/DoSomething/mbp-user-import/wiki/7.-Problems-%5C--Solutions)

#### 1. [mbp-user-import](https://github.com/DoSomething/mbp-user-import)

An application (producer) in the Quicksilver (Message Broker) system. Imports user data from CVS formatted files that create message entries in the `userImportQueue`.

#### 2. [mbc-user-import](https://github.com/DoSomething/mbc-user-import)

An application (consumer) in the Quicksilver (Message Broker) system. Processes user data import messages in the `userImportQueue`.

#### 3. [mbp-logging-reports](https://github.com/DoSomething/Quicksilver-PHP/tree/master/mbp-logging-reports)

Generate reports of the on going user import process. Reports are sent through email and Slack.

---

## mbp-user-import

### Installation

**Production**
- `$ composer install --no-dev`

**Development**
- `*composer install --dev`

### Update

- `$ composer update`

###Gulp Support
Use a path directly to gulp `./node_modules/.bin/gulp` or add an alias to your system config (`.bash_profile`) as `alias gulp='./node_modules/.bin/gulp'`

See `gulpfile.js` for configuration and combinations of tasks.

####Linting
- `gulp lint`

### Test Coverage

**Run all tests**
- `$ ./vendor/bin/phpunit --verbose tests`

or
- `$ npm test`

or
- `$ gulp test`

### PHP CodeSniffer

- `php ./vendor/bin/phpcs --standard=./ruleset.xml --colors -s mbp-user-import.php mbp-user-import.config.inc mbp-user-import_manageData.php mbp-user-import_manageData.config.inc src bin tests`
Listing of all coding volations by file.

- `php ./vendor/bin/phpcbf --standard=./ruleset.xml --colors mbc-user-import.php mbc-user-import.config.inc mbp-user-import_manageData.php mbp-user-import_manageData.config.inc src bin tests`
Automated processing of files to adjust to meeting coding standards.

**References**:
Advanced-Usage
- https://github.com/squizlabs/PHP_CodeSniffer/wiki/Advanced-Usage
Annotated ruleset.xml
- https://pear.php.net/manual/en/package.php.php-codesniffer.annotated-ruleset.php


### Watch Files

Runs PHPUnit tests and basic PHP Lint in a watchful state.

- `gulp`

###From Command Line

**mbp-user-import.php**
`$ php mbp-user-import.php <enviroment>`
- Enviroment: <test | dev | prod>

**mbp-user-import_manageData.php**

`$ php mbp-user-import_manageData.php <enviroment> <source> <page> <start date>`
- Enviroment: <test | dev | prod>
- Source: <Niche | AfterSchool | mobileapp_ios | mobileapp_android>
- Page: optional, defaults to 1.
- Start Date: optional, defaults to the start of yesterday.
  - Format YYYY-MM-DD
  - "all" to get all available data for `source` value.
  - "today" for a date for yesterday
