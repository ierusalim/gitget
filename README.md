# gitget

Simple tool for get information from github.com
Packaged in gitget.phar and can use as 'php gitget.phar <parameters>'

This is a help for console version (see src/console_help.txt)
```
usage: gitget <user_repo> [<local_path>]

<user_repo>  - may be specified as 'user/repository' or full github url
<local_path> - [optional] path for put downloaded content.
               (do not downloads if local_path not specified)
Examples: ------------
  gitget symfony        - show list of repositories by git-user 'symfony'
  gitget laravel/lumen  - show information about repository 'laravel/lumen'
  gitget php-fig/log .  - download all files from repository 'php-fig/log'
                          to current path (from which the gitget is started)
----------------------
Local path may be dot-begin specified as ./folder for add it to current path

Can use mask with * character for specified folders, extentions, etc.

Examples: -------------
  gitget symfony/flex/*test.php ./flextst
 - download all files *test.php files from repository symfony/flex to ./flextst
  gitget symfony/flex/tests/* ./flextst
 - download all files from /tests folder of repository symfony/flex to ./flextst
-----------------------
Local path can be specified as /abspath or D:\path for Windows

Character '+' in path expanded as user/repository part of path
Examples: -------------
  gitget laravel/tinker /var/www/vendor/+
 -download all from repository laravel/tinker to /var/www/vendor/laravel/tinker/

  gitget https://github.com/laravel/tinker D:\OpenServer\vendor\+
- Same as in the previous example, but <user_repo> specified as url and windpath
--------------------------------------------------------------------------------
ATTN: gitget bypasses file-by-file and it can be slow for large repositories.
if you need quick download all data from repository best to use git or composer.
```
