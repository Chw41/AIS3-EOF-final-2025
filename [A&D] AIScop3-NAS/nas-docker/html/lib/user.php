<?php

function parse_etc_passwd() {
    $file = file_get_contents("/etc/passwd");
    $lines = explode("\n", $file);
    $users = [];
    foreach ($lines as $line) {
        if (strlen(trim($line)) == 0) {
            continue;
        }

        $parts = explode(":", $line);
        $username = $parts[0];
        $uid = $parts[2];
        $gid = $parts[3];

        if ($uid < 1000) {
            continue;
        }
        $users[$username] = array(
            "uid" => $uid,
            "gid" => $gid
        );
    }
    return $users;
}

function parse_etc_group() {
    $passwd_users = parse_etc_passwd();

    $file = file_get_contents("/etc/group");
    $lines = explode("\n", $file);
    $groups = [];

    foreach ($lines as $line) {
        if (strlen(trim($line)) == 0) {
            continue;
        }

        $parts = explode(":", $line);
        $groupname = $parts[0];
        $gid = $parts[2];
        $users = explode(",", $parts[3]);

        if ($gid < 1000) {
            continue;
        }

        foreach ($passwd_users as $user => $data) {
            if ($data["gid"] == $gid) {
                $users[] = $user;
            }
        }

        $groups[$groupname] = array(
            "gid" => $gid,
            "users" => $users
        );
    }
    return $groups;
}

function user_get_groups($username) {
    $groups = parse_etc_group();
    $user_groups = [];
    foreach ($groups as $group => $data) {
        if (in_array($username, $data["users"])) {
            $user_groups[] = $group;
        }
    }
    return $user_groups;
}

function user_is_admin($username) {
    $groups = user_get_groups($username);
    return in_array("admin", $groups);
}