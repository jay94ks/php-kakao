<?php

namespace jay94ks\kakao\user;

/**
 * API Constants for implementing Kakao User Management.
 *
 * @author jay94ks
 */
class Api {
    const TOKEN_URL = "https://kapi.kakao.com/v1/user";
    const TOKEN_PATH_LOGOUT = '/logout';
    const TOKEN_PATH_UNLINK = '/unlink';
    const TOKEN_PATH_GETINFO = '/access_token_info';
    
    const USER_URL = "https://kapi.kakao.com/v2/user";
    const USER_V1_URL = "https://kapi.kakao.com/v1/user";
    const USER_PATH_GETINFO = '/me';
    const USER_PATH_UPDATEPROP = '/update_profile';
}
