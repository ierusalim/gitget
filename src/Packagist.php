<?php

namespace ierusalim\GitGet;

use ierusalim\GitRepoWalk\GitRepoWalk;
 
/**
 * Description of Packagist
 *
 * @author Alexander Jer <alex@ierusalim.com>
 */
class Packagist extends GitRepoWalk
{
    /**
     * Get data about repository user/repo from Packagist.org
     * 
     * @param string $user_repo
     * @return array|boolean
     */
    public function getVersionListByPackagist($user_repo) {
        extract($this->userRepoPairDivide($user_repo, 3));
        $pair_low = strtolower($git_user.'/' . $git_repo);
        $src_url = 'https://packagist.org/p/' . $pair_low . '.json';
        $json_raw = $this->httpsGetContentsOrCache($src_url,260);
        $json_arr = json_decode($json_raw,true);
        if(!isset($json_arr['packages'][$pair_low])) {
            if(isset($json_arr['error'])) {
                $err=$json_arr['error'];
            } else {
                $err=['message'=>'Unknown error','code'=>500];
            }
            throw new \Exception($err['message'],$err['code']);
        }
        $versions_arr=[];
        foreach($json_arr['packages'][$pair_low] as $version=>$dist_arr) {
            $versions_arr[$version]=[
                'version_normalized'=>$dist_arr['version_normalized'],
                'time'=>$dist_arr['time'],
                'dist_url'=>$dist_arr['dist']['url'],
                'authors'=>$dist_arr['authors'],
                'require'=>$dist_arr['require'],
            ];
        }
        return $versions_arr;
    }
    
    /**
     * Return compact strings-array about versions user/repo from Packagist
     * 
     * @param string $user_repo
     * @return boolean|array
     */
    public function showVersionsArr($user_repo) {
        try {
            $versions_arr = $this->getVersionListByPackagist($user_repo);
        } catch(\Exception $e) {
            return false;
        }
        $ret_arr=[];
        if(!is_array($versions_arr)) return false;
        foreach($versions_arr as $version=>$v_arr) {
            $require_arr=[];
            foreach($v_arr['require'] as $what=>$v) {
                $require_arr[]=$what.$v;
            }
            $req_str = implode(' , ',$require_arr);
            if (strlen($req_str) > 63) {
                $req_str = substr($req_str, 0, 60) . '...' . count($require_arr);
            }
            $ret_arr[] =
                str_pad($version, 16)
                . ' ' . substr($v_arr['time'],0,10)
                . ' require: ' . $req_str;
        }
        return $ret_arr;
    }
    
    /**
     * Show data about version releases user/repo and return in one string
     * 
     * @param string $user_repo
     * @return string
     */
    public function showVersionsStr($user_repo) {
        $vers_str_arr = $this->showVersionsArr($user_repo);
        if(is_array($vers_str_arr)) {
            return "Version Releases of $user_repo (by Packagist):\n"
                . implode("\n",$vers_str_arr)
                . "\n";
        } else {
            return "Not found information about $user_repo in Packagist\n";
        }
    }
}
