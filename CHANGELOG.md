Orchid changelog
====
The latest version of this file can be found at the master branch of the
Orchid repository.

## 0.3.5 (2017-07-01)
- Add Dependency Injection Container (PSR-11)

### 0.3.4 (2017-06-24)
- Fix Router doc hint highlight

### 0.3.3 (2017-02-18)
- Fix displaying exceptions in the templates
- Fix passing data to the template

### 0.3.2 (2017-02-18)
- Fix Controller comment
- Fix Collection getting keys
- Fix Collection iterator
- Reworked logic Router `__invoke` method
- Removed error absorption in App
- Removed unnecessary check in View

### 0.3.1 (2016-12-31)
- Fix default_mimetype to text/plain

### 0.3.0 (2016-11-30)
- Add support PSR-2 (reformat all code)
- Add composer support
- Add support PRS-4
- Add support PSR-7
- Add Middleware functional
- Add Route and RouteGroup classes
- Add many interfaces of entity
- Add method __toString in Collection and Model classes
- Fix class Router
- Fix ob_implicit_flush() expects parameter 1 to be integer, boolean given
- Rename Collection method collect to where
- Implements Collection interfaces ArrayAccess, Countable, IteratorAggregate
- Changed namespace
- Changed classes Request, Response (now in Http package)
- Removed support of Daemon
- Removed support Events
- Removed support Database (now in other sub-project Orchid-Database)
- Removed support Memory (now in other sub-project Orchid-Memory)
- Removed all Extensions (now in other sub-project Orchid-Misc)
- Removed Validator (now in other sub-project Orchid-Filter)
- Removed all Examples
