<?php

/**
 * This file contain autorun-script for gitget.phar
 * 
 * PHP Version 5.6
 * 
 * @package    ierusalim\gitget
 * @author     Alexander Jer <alex@ierusalim.com>
 * @copyright  2017, Ierusalim
 * @license    Apache License 2.0
 */

namespace ierusalim\GitGet;

//need for debugging 
$inc = __DIR__ ."/../../../ierusalim/github-repo-walk/src/GitRepoWalk.php";
if(is_file($inc)) require $inc;
else require __DIR__ ."/../vendor/ierusalim/github-repo-walk/src/GitRepoWalk.php";

require __DIR__ ."/GitGet.php";
require __DIR__ ."/Packagist.php";

$g = new GitGet();

$tmp_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR .'gitget';
if($g->checkDirMkDir($tmp_path)) {
    $g->cacheGetContentsActivate($tmp_path);
}

//get console arguments in temporary array, without [0] argument where start-file
$args_arr=array_slice($argv,1);

//first pass: looking for github-links in arguments
foreach($args_arr as $k=>$arg) {
    $ret = $g->GitHubLinkParse($arg);
    if(!$ret) continue;
    if(!empty($git_url)) die("ERROR: Double github-links found in arguments\n");
    extract($ret);
    $git_url = $arg;
    if(!empty($git_path)) {
        $git_mask = $git_path;
    }

    unset($args_arr[$k]);
}

//second pass: looking for user/repo
foreach($args_arr as $k=>$arg) {
    switch($arg) {
    case '--argv':
        echo "Current File:".__FILE__ ."\n";
        print_r($argv);
    case '--args':
        $show_args=true;
    case '-?':
    case '/?':
    case '--help':
    case '/help':
        if(empty($show_args)) {
            console_help_show();
        }
        unset($args_arr[$k]);
        continue 2;
    case '-v':
    case '--versions':
        $versions_mode = true;
        unset($args_arr[$k]);
        continue 2;
 
    
    }
    //only if git_url still not recognized
    if(empty($git_url)) {
        $ret = $g->checkUserRepoInter($arg);
        if($ret) {
            extract($ret);
            if(!empty($git_path)) {
                $git_mask = $git_path;
            }
            unset($args_arr[$k]);
            continue;
        }
    }
    
    //seek local path
    $ret = $g->validateLocalDir($arg);
    if($ret) {
        if(empty($local_path)) {
            if(substr($arg,0,1)=='.') {
                if(strlen($arg)===1 || strpos('\\/',substr($arg,1,1))!==false) {
                    $arg = getcwd() . substr($arg,1);
                } else {
                    continue;
                }
            }
            $local_path = $arg;
            unset($args_arr[$k]);
        }
    }
}

if(count($args_arr) == 1) { 
    // If there is only one argument that can be interpreted as a git-user name
    $git_user = implode($args_arr);
    if($g->gitUserNameValidate($git_user)) {
        $args_arr=[];
    }
}
if(count($args_arr)) {
    echo "Unrecognized argument" . ((count($args_arr)>1) ? 's: ' : ': ');
    foreach($args_arr as $arg) { echo $arg ."\n\t"; }
    die("\nUse: gitget -? for help\n");
}

//show args if need
if(!empty($show_args)) {
    echo (empty($git_url))?"Git-url unrecognized\n":"Git-url: $git_url\n";
    if(!empty($git_user)) {
        echo "Git-user: $git_user\n";
    }
    if(!empty($git_repo)) {
        echo "Git-repo: $git_repo\n";
    }
    if(!empty($git_mask)) {
        echo "Git-mask: $git_mask\n";
    }
    if(!empty($git_branch)) {
        echo "Git-branch: $git_branch\n";
    }
    if(!empty($local_path)) {
        echo "Output path: $local_path\n";
    }
    die;
}

if(!empty($local_path)) {
    $local_path = strtr($local_path, '/' , DIRECTORY_SEPARATOR);
    if(!\is_dir($local_path)) {
        $dir_name=\dirname($local_path);
        if(!empty($dir_name) && !\is_dir($dir_name)) { //can create only 2 subfolder
            $dir_name = \dirname($dir_name);
            if(!empty($dir_name) && !\is_dir($dir_name)) {
                die("Path not found $local_path\nBreak\n");
            }
        }
    }    //expanding '+' in local_path to 'user/repo'
    $i=strpos($local_path, '+');
    if($i !==false) {
        if(!empty($git_user) && !empty($git_repo)) {
            $left_part = substr($local_path,0,$i);
            $right_part = substr($local_path,$i+1);
            if(empty($left_part)) $left_part = getcwd();
            $local_path = $g->pathDs($left_part) . $git_user;
            //if(!is_dir($local_path)) mkdir($local_path);
            $local_path.= DIRECTORY_SEPARATOR . $git_repo;
            //if(!is_dir($local_path)) mkdir($local_path);
            if(!empty($right_part)) {
                $local_path.=$right_part;
                //if(!$g->checkDirMkDir($local_path)) {
                //    die("Can't create $local_path\n");
                //}
            }
        }
    }
    echo "Local path: $local_path\n";
}

//if branch presumably defined
if(!empty($git_branch)) {
    $may_be_branch = $git_branch;
}

if(empty($git_user)) {
    console_help_show();
    die;
}

/*
while(empty($git_url)) {    
    echo "Enter github url:";
    $git_url = trim(fgets(STDIN));
    $ret = $g->githubLinkParse($git_url);
    if($ret) {
        extract($ret);
        break;
    } else {
        $ret = $g->checkUserRepoInter($git_url);
        if($ret) {
            extract($ret);
            $git_url = 'https://github.com/'.$git_user . '/' . $git_repo;
            if (!$ret = $g->githubLinkParse($git_url)) {
                unset($git_url);
            } else {
                break;
            }
        }        
    }
    echo "Unrecognized url\n";
}
*/

//if(empty($git_url)) die("Unrecognized url\n");

if(!empty($git_user)) {
    if(empty($git_repo)) {
        //if specified only user - list his repositories
        try {
            $repo_list = $g->getUserRepositoriesList($git_user);
        } catch(\Exception $e) {
            die($e->getMessage() . "\n");
        }
        echo "$git_user: ".count($repo_list) ." repositories\n\n";
        
        try {
            $p = new Packagist();
            $packagist_repo_arr = $p->getRepositoriesInPackagist($git_user);
        } catch (Exception $ex) {
            $packagist_repo_arr = [];
        }
        
        foreach($repo_list as $repo_obj) {
            $git_user_and_repo = $git_user.'/'.$repo_obj['name'];
            $pair_low = strtolower($git_user_and_repo);
            echo str_pad($git_user_and_repo,32);
            //if(!empty($repo_obj['description'])) echo "\t[" .$repo_obj['description']."]";
            
            if(\in_array($pair_low,$packagist_repo_arr)) {
                echo "[composer]";
            } else {
                echo "          ";
            }
            
            if(!empty($repo_obj['fork'])) {
                echo " [fork] ";
            } else {
                if(empty($contacts)) {
                    try {
                        $contacts = $g->getRepositoryContacts($git_user_and_repo);
                    } catch(\Exception $e) {
                        echo " ... ".$e->getMessage();
                    }
                }
            }
            echo "\n";
        }
        if(!empty($contacts)) {
            echo "\n contact" . ((count($contacts)>1)?"s:\n":': ');
            foreach($contacts as $email=>$roles) {
                echo $email . ' -- '. $roles[0]['name']."\n"; 
            }
        }
        die("\n");
    } else {
        //if specified git_user and git_repo
        if(empty($git_branch) && !empty($may_be_branch)) $git_branch = $may_be_branch;
        try {
            $pair = $git_user.'/'.$git_repo;
            $repo_files_stat = $g->getRepoFilesStat($pair, $git_branch);
        } catch (Exception $ex) {
            die($e->getMessage());
        }
        if(empty($local_path)) {
            echo "Information about repository $pair \n";
            print_r($repo_files_stat);

            $p = new Packagist();
            echo $p->showVersionsStr($git_user.'/'.$git_repo);            
        }
        if(!empty($local_path)) {
            //$g->writeEnableOverwrite();
            $g->writeEnable();
            
            if(!empty($git_mask)) {
                //echo "Set mask: $git_mask\n";
                $g->setMaskFilter($git_mask);
            }
             //download mask-filtered files from repository to local-path
            $stat = $g->gitRepoWalk( 
                $local_path,
                $pair,
                $git_branch
            );

            print_r($stat);

        }
    }
}

function console_help_show() {
    static $shown=false;
    if($shown) return;
    readfile(__DIR__ . '/console_help.txt');
    $shown=1;
}

__HALT_COMPILER();
