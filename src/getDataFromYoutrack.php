<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__.'/authenticationAndSecurity.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__.'/getCustomSettings.php';

class getDataFromYoutrack {
    function restResponse($url, $postOrGet = 'get', $headers = null, $body = null, $options = null){
        $client = new \Guzzle\Http\Client();
        $authenticationAndSecurity = new authenticationAndSecurity;        
        $authentication =  $authenticationAndSecurity->getAuthentication();
        if(  $authentication['type'] !== 'password' && $authentication['type'] !== 'cookie' && $authentication['type'] !== 'file'){
            echo 'authentication type unknown. please check its set in the customSettings.php file';
            return;
        }
        if( !isset($options) ){
            if( $authentication['type'] === 'password'){
                $options = ['auth' => [ $authentication['details']['user'], $authentication['details']['password'] ]];
            }else{
                $options = [];
            }
        }
        if($postOrGet === 'get'){
           $request = $client->get($url, $headers , $options );
        } elseif($postOrGet === 'post') {
           $request = $client->post($url, $headers , $body, $options );
        } elseif($postOrGet === 'put') {
           $request = $client->put($url, null , $body, $options );
        }
        if( isset($headers) ){
            foreach($headers as $key => $value){
                $request->addHeader($key,$value);
            }
        }
        if($authentication['type'] === 'cookie'){
            foreach($authentication['details'] as $singleCookie){
                foreach($singleCookie as $cookieName => $cookieValue){
                    $request->addCookie( $cookieName, $cookieValue );
                }
            }
        }
        $request->send(); 
        return $request;
    }
    function rest($url, $postOrGet = 'get', $headers = null, $body = null, $options = null, $cachable = true ){
        if( $cachable == true ){
            if( $GLOBALS['cache'] && $postOrGet == 'get' ){
                $cacheClass = new cache;
                $cached = $cacheClass->getCached($url);
            }else{
                $cached = false;
            }
        }else{
            $cached = false;
        }
        if( !$cached  ){
            $res = $this->restResponse($url, $postOrGet, $headers, $body, $options);
            $res = $res->getResponse();
            $response = $res->getBody();
            if( $GLOBALS['cache'] && $postOrGet == 'get' ){
                $cacheClass->createCache($url, $response);
            }
           return $response;
        }else{
            return $cached;
        }
    }
    /*
     * $whereAttr array of required attribute value pairs  for desired node e.g. [ 'attr'=>'value', 'attr2'=>'value2' ]
     */
    function extract_data_xml( $xml, $node, $attribute='', $return_data = [], $whereAttr=[] ){
        $empty = true;
        $reader = new XMLReader();
        $reader->xml($xml);
        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT) {
                $exp = $reader->expand();
                if ($exp->nodeName == $node){
                    $continue = true;
                    foreach ( $whereAttr as $attr => $val ){
                        if( $exp->getAttribute($attribute) == $val ){
                            $continue = false;
                        }
                    }
                    if( $continue ){
                        if( $attribute === ''){
                             array_push( $return_data, $exp->nodeValue );    
                        }else{
                             array_push( $return_data, $exp->getAttribute($attribute) );
                        }
                        $empty = false;
                    }
                }
            }
        }
        return [ $return_data, $empty ];
    }

    
    function get_custom_fields($project='' ){
        global $youtrack_url;
        if( $project == '' ){
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        $url = $youtrack_url.'/rest/admin/project/'.$project.'/customfield'; 
        $youtrack_project_customfields_xml = $this->rest($url,'get');
        list($youtrack_project_customfields, $empty) = $this->extract_data_xml( $youtrack_project_customfields_xml, 'projectCustomField', 'name');
        $key = array_search('Assignee', $youtrack_project_customfields);
        if( $key !== false) {
            unset($youtrack_project_customfields[$key]); // Assignee is not a custom field
        }
        return $youtrack_project_customfields;
    }
    function getCustomFieldTypeAndBundle($youtrack_url, $user, $password, $youtrack_fields_list = '', $project=''){
        if( $project == '' ){
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        if( $youtrack_fields_list == '' ){
            $youtrack_fields_list = $this->get_custom_fields($project);
        }
        foreach($youtrack_fields_list as $field){
            $url = $youtrack_url.'/rest/admin/project/'.$project.'/customfield/'.$field; 
            $customField = $this->rest($url, 'get');
            list( $CustomFieldtypeArray, $empty ) = $this->extract_data_xml( $customField, 'projectCustomField', 'type');
            $customFieldSettings[$field]['fieldType'] = $CustomFieldtypeArray[0];
            // if dropdown field
            if( strpos($CustomFieldtypeArray[0], '[') !== false ){
                list( $bundle, $empty ) = $this->extract_data_xml( $customField, 'param', 'value', [], ['name'=>'bundle'] );
                $customFieldSettings[$field]['bundle'] = $bundle[0];
            }else{
                $customFieldSettings[$field]['bundle'] = '';
            }
        }
        return $customFieldSettings;
    }
    /*
     * $customFieldTypeAndBundle array from getCustomFieldTypeAndBundle
     */
    function get_custom_fields_details($youtrack_url, $user, $password, $youtrack_fields_list='', $project='', $customFieldTypeAndBundle='' ){
        if( $project == '' ){
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        if( $youtrack_fields_list == '' ){
            $youtrack_fields_list = $this->get_custom_fields($project);
        }
        if( $customFieldTypeAndBundle == '' ){
            $customFieldTypeAndBundle = $this->getCustomFieldTypeAndBundle($youtrack_url, $user, $password, $youtrack_fields_list, $project);      
        }
        
        foreach($youtrack_fields_list as $field){
           // if dropdown field
            if( strpos($customFieldTypeAndBundle[$field]['fieldType'], '[') !== false ){
                $fieldTypeShort = explode('[',$customFieldTypeAndBundle[$field]['fieldType']) [0];
                $fieldTypeShort = strtolower($fieldTypeShort);
                if($fieldTypeShort == 'enum'){
                    $url = $youtrack_url.'/rest/admin/customfield/bundle/'.$customFieldTypeAndBundle[$field]['bundle'];
                    $bundleXml = $this->rest($url, 'get');
                    list($youtrack_fields[$field], $empty) = $this->extract_data_xml( $bundleXml, 'value'); 
                }else{
                    $url = $youtrack_url.'/rest/admin/customfield/'.$fieldTypeShort.'Bundle/'.$customFieldTypeAndBundle[$field]['bundle'];
                    $bundleXml = $this->rest($url, 'get');
                    list($youtrack_fields[$field], $empty) = $this->extract_data_xml( $bundleXml, $fieldTypeShort); 
                }
            }else{
                $youtrack_fields[$field]='';
            }
        }
        return $youtrack_fields;
    }
    function get_custom_fields_with_details($youtrack_url, $user, $password, $project=''){
        if( $project == '' ){
            $projectList = $this->getProjectsList();
            $project = $projectList[0];
        }
        $youtrack_fields_list = $this->get_custom_fields($project);
        $customFieldDetails = getCustomFieldTypeAndBundle($youtrack_url, $user, $password, $youtrack_fields_list, $project);
        $youtrack_fields = $this->get_custom_fields_details($youtrack_url, $user, $password, $youtrack_fields_list, $project, $customFieldDetails);
        return [$youtrack_fields_list, $youtrack_fields];
    }
   

    
    function getProjectsList(){
        global $youtrack_url;
        $url = $youtrack_url.'/rest/admin/project'; 
        $youtrack_projects_list_xml = $this->rest($url, 'get');
        list($youtrack_projects_list, $empty) = $this->extract_data_xml( $youtrack_projects_list_xml, 'project', 'id');
        return $youtrack_projects_list;
    }
    function getProjectAssignees($project,$youtrack_url, $user, $password){
        $url = $youtrack_url.'/rest/admin/project/'.$project.'/assignee'; 
        $youtrack_project_assignees_xml = $this->rest($url, 'get');
        list($youtrack_project_assignees, $empty) = $this->extract_data_xml( $youtrack_project_assignees_xml, 'assignee', 'login');
        return $youtrack_project_assignees;
    }
    
    function get_users(){
        global $youtrack_url;
        $user_list = [];
        $request_end = '';
        $loop = true;
        $users_no = 0;
        while( $loop == true ){
            $url = $youtrack_url.'/rest/admin/user'.$request_end; 
            $user_list_xml = $this->rest($url, 'get');
            list($user_list, $empty) = $this->extract_data_xml( $user_list_xml, 'user', 'login', $user_list);
            $request_end = '?start='.$users_no;
            $users_no += 10;
            if( $empty ){
                $loop = false;
            }
        }
        return $user_list;
    }
    
    
}
