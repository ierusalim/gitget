<?php
namespace ierusalim\GitGet;

use ierusalim\GitRepoWalk\GitRepoWalk;

class GitGet extends GitRepoWalk {
    
    /**
     * Parsing github urls for recognize git_user, git_repo, etc.
     * 
     * @param string $url
     * @return array|bool
     */
    public function githubLinkParse($url) {
        
        $git_user = $git_repo = $git_branch = $git_type = $git_path = NULL;
        
        $u = $this->gitHubLinkFirstParse($url);
        if(!isset($u['host'])) return false;
        extract($u);
        if(!empty($git_user)) {
            if($this->checkGitUserStoplist($git_user)) {
                $git_type = $git_user;
                $git_user = $git_repo = $git_branch = $git_path = NULL;
            }
        }
        if(!empty($git_repo)) {
            if(substr(strtolower($git_repo),-4)=='.git') {
                $git_repo = substr($git_repo,0,-4);
            }
        }
        return compact(
            'host',
            'git_user',
            'git_repo',
            'git_branch',
            'git_type',
            'git_path'
        );
    }
    
    /**
     * First parsing github urls for recognize git_user, git_repo, etc.
     * 
     * @param string $url
     * @return array
     */
    private function gitHubLinkFirstParse(
        $url
    ) {
        $i=strpos($url,'?');
        if($i) $url = substr($url,0,$i);
        $i=strpos($url,'#');
        if($i) $url = substr($url,0,$i);
        
        $host_bases = ['github.com','githubusercontent.com'];
        //Example link:
        //https://github.com/ierusalim/github-repo-walk/tree/master/src
        foreach($host_bases as $host_base) {
            $hbl = strlen($host_base);
            $u = explode('/',$url);
            for($i=0;$i<count($u);$i++) {
                if(strtolower(substr($u[$i], -$hbl)) === $host_base) {
                    $host = strtolower($u[$i]);
                    $u=array_slice($u, $i+1);
                    break;
                }
            }

            if (empty($host) || count($u) < 1) continue;

            switch($host) {
            case 'www.github.com':
            case 'github.com':
                switch (count($u)) {
                case 1:
                    return [
                        'host' => $host,
                        'git_user' => $u[0]
                    ];
                case 2:
                    return [
                        'host' => $host,
                        'git_user' => $u[0],
                        'git_repo' => $u[1]
                    ];
                case 3:
                    return [
                        'host' => $host,
                        'git_user' => $u[0],
                        'git_repo' => $u[1],
                        'git_type' => $u[2]
                    ];
                default:
                    return [
                    'host' => $host,
                    'git_user' => $u[0],
                    'git_repo' => $u[1],
                    'git_branch' =>$u[3],
                    'git_type' => $u[2],
                    'git_path' => implode('/',array_slice($u,4))
                    ];
                }
                
            case 'raw.githubusercontent.com':
                return [
                    'host'=>$host,
                    'git_user'=>$u[0],
                    'git_repo'=>$u[1],
                    'git_branch'=>$u[2],
                    'git_type'=>'blob',
                    'git_path'=>implode('/',array_slice($u,3))
                ];
            case 'api.github.com':
                switch($u[0]) {
                case 'users':
                    return [
                        'host'=>$host,
                        'git_user'=>$u[1],
                    ];
                case 'repos':
                    return [
                        'host'=>$host,
                        'git_user'=>$u[1],
                        'git_repo'=>$u[2]
                    ];
                }
            default:
                return [
                    'host'=>$host,
                    'git_type'=>'unknown',
                    'pars'=>$u,
                ];
            }
            break;
        }
        return [];
    }
    
    /**
     * Checks the string for interpretation as user/repo
     * @param string $st
     * @return array|bool
     */
    public function checkUserRepoInter($git_user_and_repo) {
        $i=strcspn($git_user_and_repo, '/\\');
        if($i === false) return false;
        $git_user = trim(substr($git_user_and_repo, 0, $i));
        if(empty($git_user)) return false;
        if(!$this->gitUserNameValidate($git_user)) return false;
        $git_repo = trim(substr($git_user_and_repo, $i+1));
        $i=strpos($git_repo,'#');
        $git_branch=NULL;
        if($i !== false) {
            $git_branch = trim(substr($git_repo,$i+1));
            $git_repo = trim(substr($git_repo,0,$i));
        }
        if($git_repo === '*') {
            $git_repo = NULL;
        } else {
            if(!$this->gitRepoNameValidate($git_repo)) return false;
        }
        return compact('git_user','git_repo','git_branch');
    }

    /**
     * Validate github user name, return false if invalid name
     * @param string $git_user
     * @return boolean
     */
    public function gitUserNameValidate($git_user) {
        if(empty($git_user)) return false;
        if(strlen($git_user)>39) return false;
        if (!preg_match('/^[A-Za-z0-9\-]*$/', $git_user)) return false;
        if(substr($git_user,0,1)=='-' || substr($git_user,-1)=='-') return false;
        if($this->checkGitUserStoplist($git_user)) return false;
        return true;
    }

    /**
     * Check whether the git-user name is in the stop-list
     * 
     * @param string $git_user
     * @return boolean
     */
    private function checkGitUserStoplist($git_user) {
        if(empty($git_user)) return false;
        return 
            in_array(strtolower($git_user), [
                'search','features','business', 'explore','marketplace',
                'pricing','settings','help','contact','pulls','issue'
            ]);
    }
    
    /**
     * Valisate git-repo name, return false if invalid
     * 
     * @param string $git_repo
     * @return boolean
     */
    public function gitRepoNameValidate($git_repo) {
        if(empty($git_repo)) return false;
        if(strlen($git_repo)>100) return false;
        if (!preg_match('/^[A-Za-z0-9\-\_]*$/', $git_repo)) return false;
        return true;
    }
    
    public function getRepoFilesStat($git_user_and_repo, $git_branch = NULL) {
        $repo_files = $this->getRepoFilesList($git_user_and_repo, $git_branch);
        $files_cnt = 0;
        $subfolders = 0;
        $total_size = 0;
        foreach($repo_files->tree as $git_fo) {
            if($git_fo->type =='blob') {
                $files_cnt++;
                $total_size+=$git_fo->size;
            } elseif($git_fo->type == 'tree') {
                $subfolders++;
            }
        }
        $branch = $git_branch;
        $inter_obj=compact('files_cnt','subfolders','total_size','branch');
        $pair_low = strtolower($git_user_and_repo);
        if(isset($this->cachedRepositoryInfo[$pair_low])) {
            $repo_obj = $this->cachedRepositoryInfo[$pair_low];
            foreach($this->interestingRepoPars as $repoPar) {
                if(isset( $repo_obj->{$repoPar})) {
                    $inter_obj[$repoPar] = $repo_obj->{$repoPar};
                }
            }
        }
        return $inter_obj; //compact('files_cnt','subfolders','total_size','git_branch');
    }
}
