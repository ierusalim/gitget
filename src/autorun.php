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

require __DIR__ ."/../vendor/ierusalim/github-repo-walk/src/GitRepoWalk.php";
//require __DIR__ ."/../../../ierusalim/github-repo-walk/src/GitRepoWalk.php";
require __DIR__ ."/GitGet.php";

$g = new GitGet();

//get console arguments in temporary array, without [0] argument where start-file
$args_arr=array_slice($argv,1);

//first pass: looking for github-links in arguments
foreach($args_arr as $k=>$arg) {
    $ret = $g->GitHubLinkParse($arg);
    if(!$ret) continue;
    if(!empty($git_url)) die("ERROR: Double github-links found in arguments\n");
    extract($ret);
    $git_url = $arg;
    unset($args_arr[$k]);
}

//second pass: looking for user/repo
foreach($args_arr as $k=>$arg) {
    switch($arg) {
    case '.':
        $arg = getcwd();
        //echo "Dot replaced as $arg \n";
        break;
    case '--argv':
        echo "Current File:".__FILE__ ."\n";
        print_r($argv);
    case '--args':
        $show_args=true;
        unset($args_arr[$k]);
        continue 2;
    }    
    //only if git_url still not recognized
    if(empty($git_url)) {
        $i=strpos($arg,'*');
        if($i) {
            $mask_arr = explode('/',$arg);
            if(count($mask_arr)>2) {
                $git_mask = implode(array_slice($mask_arr,2));
                $arg = $mask_arr[0].'/'.$mask_arr[1];
            }
        }
        $ret = $g->checkUserRepoInter($arg);
        if($ret) {
            extract($ret);
            $git_url = 'https://github.com/'.$git_user . '/' . $git_repo;
            if (!$ret = $g->githubLinkParse($git_url)) {
                unset($git_url);
            } else {
                unset($args_arr[$k]);
                continue;
            }
        }
    }
    
    //seek local path
    $ret = $g->validateLocalDir($arg);
    if($ret) {
        if(empty($local_path)) {
            $local_path = $arg;
            unset($args_arr[$k]);
        }
    }
}
if(count($args_arr)) {
    echo "Unrecognized argument" . ((count($args_arr)>1) ? 's: ' : ': ');
    foreach($args_arr as $arg) { echo $arg ."\n\t"; }
    die("\n");
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

if(!empty($local_path) && !is_dir($local_path)) die("Path not found $local_path\n");

//if branch presumably defined
if(!empty($git_branch)) {
    $may_be_branch = $git_branch;
}

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

if(empty($git_url)) die("Unrecognized url\n");

if(!empty($git_user)) {
    if(empty($git_repo)) {
        //if specified only user - list his repositories
        try {
            $repo_list = $g->getUserRepositoriesList($git_user);
        } catch(\Exception $e) {
            die($e->getMessage());
        }
        echo "$git_user: ".count($repo_list) ." repositories\n\n";
        foreach($repo_list as $repo_obj) {
            $git_user_and_repo = $git_user.'/'.$repo_obj['name'];
            echo $git_user_and_repo;
            //if(!empty($repo_obj['description'])) echo "\t[" .$repo_obj['description']."]";
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
            echo "\nContact" . ((count($contacts)>1)?"s:\n":': ');
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
        }
        if(!empty($local_path)) {
            //$g->writeEnableOverwrite();
            $g->writeEnable();
            
            if(!empty($git_mask)) {
                echo "Set mask: $git_mask\n";
                $g->setMaskFilter($git_mask);
            }
             //download all files from repository to local-path
            $stat = $g->gitRepoWalk( 
                $local_path,
                $pair,
                $git_branch
            );

            print_r($stat);

        }
    }
}


__HALT_COMPILER();
