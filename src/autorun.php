<?php

namespace ierusalim\GitGet;

$out_path = getcwd();

require __DIR__ ."/vendor/ierusalim/github-repo-walk/src/GitRepoWalk.php";
require __DIR__ ."/GitGet.php";

$g = new GitGet();

//get arguments in temporary array
$args_arr=$argv;
//first pass: looking for github-links in arguments
foreach($args_arr as $k=>$arg) {
    $ret = $g->GitHubLinkParse($arg);
    if(!$ret) continue;
    if(!empty($git_url)) die("ERROR: Double github-links found in arguments\n");
    extract($ret);
    $git_url = $arg;
    unset($args_arr[$k]);
}

$have_dots = 0;

//second pass: looking for user/repo
foreach($args_arr as $arg) {
    switch($arg) {
    case '.':
        $have_dots++;
        continue;
    case '--args':
    case '--argv':
        $show_args=true;
        continue;
    }    
    //only if git_url still not recognized
    if(empty($git_url)) {
        $ret = $g->checkUserRepoInter($arg);
        if($ret) {
            extract($ret);
            $git_url = 'https://github.com/'.$git_user . '/' . $git_repo;
            if (!$ret = $g->githubLinkParse($git_url)) {
                unset($git_url);
            }
        }
    }
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
    if(!empty($git_branch)) {
        echo "Git-branch: $git_branch\n";
    }
}

//if branch presumably defined
if(!empty($git_branch)) {
    $may_be_branch = $git_branch;
}

if($have_dots) {
    echo "Output path=".$out_path."\n";
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
            echo $git_user.'/'.$repo_obj['name'] . "\n";
            //if(!empty($repo_obj['description'])) echo "\t[" .$repo_obj['description']."]";
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
        echo "Information about repository $pair \n";
        print_r($repo_files_stat);
    }
}

print_r($ret);

//$contacts = $g->getRepositoryContacts('ierusalim\github-repo-walk');
//print_r($contacts);

__HALT_COMPILER();
