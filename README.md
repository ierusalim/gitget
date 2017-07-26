# gitget
#### Simple tool for download folders from github.com
What do I do when I need to download a folder with github.com files?

I type the command line "gitget " and paste url of folder, wich I want do download.
#### How it works?
I have php-script packaged in gitget.phar and can use it as `php gitget.phar ...`
* **for linux**  I run command `ln -s /path/gitget.phar /usr/bin/gitget`
and set permission  `chmod 0755 /path/gitget.phar` for enable file execution.
* **for Windows** I added the path to the gitget.bat file in PATH environment variable

These actions make it easier to call `gitget ...` from anywhere.
### Parameters
In most cases, the parameter is url for download, for example:

`gitget https://github.com/ierusalim/gitget/`

However, such a call will only show information about the repository 'ierusalim/gitget'

To download files, you must specify the path where to filed download. For example, you may specified dot for dowload files to current path:

`gitget https://github.com/ierusalim/gitget/ .`

or, you can specified any absolute path, for example:

`gitget https://github.com/ierusalim/gitget/ /var/www/vendor/`

### Console help (see src/console_help.txt)
If you call `gitget` without parameters, this console-help will be displayed:
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
