<?php
require_once("Kj_BaseManager.php");
//#238 begin
require_once 'Net/UserAgent/Mobile.php';
//#238 end
//#242 begin
define("_CONSUMER_MYSEARCH_SEARCH_PAGE",         "_consumer_mysearch_search_page");
define("_CONSUMER_MYSEARCH_SEARCH_RESULT",       "_consumer_mysearch_search_result");
define("_CONSUMER_MYSEARCH_SEARCH_QUERY",        "_consumer_mysearch_search_query");
define("_CONSUMER_MYSEARCH_SEARCH_MODE_QUERY",   "_consumer_mysearch_search_mode_query");
define("_CONSUMER_MYSEARCH_SEARCH_FORM_ARRAY",   "_consumer_mysearch_search_form_array");
define("_ADV_NEW", 1);  // 新着検索（更新日順）
define("_ORDER_SALARY", 2);  // 給与順

//#636 End maintance process: Luvina-company
define("_PUBLIC_SEARCH_LOGINED_PATTERN_PRIOR",   1);
define("_PUBLIC_SEARCH_LOGINED_PATTERN_HISTORY_APPLY",   2);
define("_PUBLIC_SEARCH_LOGINED_PATTERN_COUNT",   3);
define("_PUBLIC_SEARCH_LOGINED_PATTERN_1",   4);
define("_PUBLIC_SEARCH_LOGINED_PATTERN_2",   5);
define("_PUBLIC_SEARCH_LOGINED_PATTERN_3",   6);
define("_PUBLIC_SEARCH_LOGINED_PATTERN_4",   7);
define("_PUBLIC_SEARCH_LOGINED_PATTERN_5",   8);
define("_PUBLIC_SEARCH_LOGINED_PATTERN_6",   9);
define("_PUBLIC_SEARCH_LOGINED_PATTERN_OTHER",   10);
//#636 End maintance process: Luvina-company
//#890 Start maintance process: Luvina-company  #comment:5
define("_PUBLIC_SEARCH_LOGINED_PATTERN_ADV_NOMINAL_PRICE",   11);
//#890 End maintance process: Luvina-company  #comment:5

//#242 end
/**
 *  Kj_ConsumerManager.php
 *
 *  @author     {$author}
 *  @package    Kj
 *  @version    $Id: skel.app_manager.php,v 1.2 2006/11/06 14:31:24 cocoitiban Exp $
 */

/**
 *  @var string  使いたいDAOをインクルード
 */
require_once('dao/TestDao.php');

require_once('dao/MemMasterDao.php');
require_once('dao/MemMailmagDao.php');
require_once('dao/MemSkillDao.php');
require_once('dao/MgzUserDao.php');
require_once('dao/MgzUserPrefDao.php');
require_once('dao/MgzUserCityDao.php');
require_once('dao/MgzUserOcpDao.php');
require_once('dao/MemApplyDao.php');
require_once('dao/MemMemRcvmailDao.php');
require_once('dao/MemExamApplyDao.php');
require_once('dao/MemMemSendmailDao.php');
require_once('dao/SysPrefectureDao.php');
require_once('dao/SysAreCityDao.php');
require_once('dao/SysOccupationDao.php');
//#{103} start maintance process: Luvina-company
require_once 'dao/SysFormDao.php';
//#{103} end maintance process: Luvina-company
//#214 start maintance process: Luvina-company
require_once 'dao/NearAreaMasterDao.php';
//#214 end maintance process: Luvina-company
require_once 'dao/AdvHelloWorkDao.php';
//#636 start maintance process: Luvina-company 
require_once 'dao/SysSkillOccupationDao.php';
//#636 end maintance process: Luvina-company
/**
 *  Kj_ConsumerManager
 *
 *  @author     {$author}
 *  @access     public
 *  @package    Kj
 */
class Kj_ConsumerManager extends Kj_BaseManager
{
	//#214 begin
	const MGZ_SEND_VOLUME_DEFAULT = 100;
	//#214 end
//#594 start maintance process: Luvina-company 
    /**
     * MemMasterDao
     *
     * @var MemMasterDao
     */
    var $memMasterDao;
    var $memSkillDao;
//#594 end maintance process: Luvina-company 

    /**
     *  Kj_ConsumerManagerのコンストラクタ
     *
     *  @access public
     *  @param  object  Ethna_Backend   &$backend   backendオブジェクト
     */
    public function __construct(&$backend)
    {
        parent::__construct($backend);
        $this->logger = $backend->getLogger();
    }

    /**
     * 認証
     */
  function auth() {

        $dao = new MemMasterDao($this->db, $this->backend);
        $retval = $dao->getList($result,array(
            "mem_mail"          => array( "=", $this->af->get('mem_mail') ),
            "mem_password"      => array( "=", $this->af->get('mem_password') ),
            "delete_flg"        => array( "=", $this->config->config['_DB_FALSE'] ),
        ),false,1);

        // DBエラー
        if($retval == $this->config->config['_DB_ERROR']) {
            $this->logger->log(LOG_ERR,"DB_ERROR ".__METHOD__."() LINE:".__LINE__);
            return Ethna::raiseError('システムエラーです。', E_CONSUMER_AUTH_AUTH);
        // 認証失敗
        } else if($retval == $this->config->config['_DB_EMPTY']) {
            $this->logger->log(LOG_INFO,"AUTH FAILED ".__METHOD__."() LINE:".__LINE__);
            return Ethna::raiseNotice('認証に失敗しました。', E_KJ_AUTH);
        // 認証成功
        } else if($retval == $this->config->config['_DB_OK']) {
            return $result;
        }

  }

    /**
     * セッション
     */
    function mySession( $session_id = false ) {

        // MemMaster
        $dao = new MemMasterDao( $this->db, $this->backend );

        // セッションIDがある場合はセット
        if ( $session_id ) {

            // 最終ログイン日時
            $now = date('Y-m-d H:i:s');

            // 属性（冗長的だけど明確に）
            $attr = array(
                'mem_session_id'        => $session_id,
                'last_login'                => $now,
                'mem_autologin_flg'    => ( $this->af->get('mem_autologin_flg') ? $this->config->config['_DB_TRUE'] : $this->config->config['_DB_FALSE'] )
            );

            // 条件（冗長的だけど明確に）
            $condition = array(
                'mem_id' => array(
                    '=', $this->session->get('MEM_ID')
                ),
            );

            // セッションIDの更新
            $retval = $dao->update( $attr, $condition );

            // DBエラー
            if($retval == $this->config->config['_DB_ERROR']) {
                $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." UPDATE mem_master FAILED ATTR:".print_r($attr,true)." COND:".print_r($condition,true));
                return Ethna::raiseError('システムエラーです。', E_CONSUMER_AUTH_MYSESSION_UPDATE);
            }

        // セッションIDが無い場合はセッションIDを返す
        } else {

            // 会員情報の呼び出し
            $retval = $dao->getList( $result, array(
                'mem_id'            => array( '=', $this->session->get('MEM_ID') )
            ), false, 1 );

            // DBエラー
            if($retval == $this->config->config['_DB_ERROR']) {
                $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." SELECT mem_master FAILED MEM_ID:".$this->session->get('MEM_ID'));
                return Ethna::raiseError('システムエラーです。', E_CONSUMER_AUTH_MYSESSION_GETLIST);
            }

            // 自動ログインからアクセスした場合はセッションが MEM_ID しか無いのでここで対策
            $this->setSessionData( $result );

            // セッションIDを返す
            return $result->mem_session_id;

        }
    }

    /**
     * セッションデータ更新
     */
    function updateSession($update_login_time=true,$update_auto_login=true) {

        // MemMaster
        $dao = new MemMasterDao( $this->db, $this->backend );

        // 会員情報の呼び出し
        $retval = $dao->getList( $result, array(
            'mem_id'            => array( '=', $this->session->get('MEM_ID') )
        ), false, 1 );

        // DBエラー
        if($retval == $this->config->config['_DB_ERROR']) {
            $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." SELECT mem_master FAILED MEM_ID:".$this->session->get('MEM_ID'));
            return Ethna::raiseError('システムエラーです。', E_CONSUMER_AUTH_UPDATESESSION_GETLIST);
        }

        // 暗号化用キー
        $cryptKey = $this->config->get('crypt_key');

        // セッションスタート
        $this->session->start();

        // http://svn.trustcity.co.jp/trac/sms-sysup/ticket/2010
        // セッションをregenerateする。
        $this->session->regenerateId();

        // セッションIDの取得
        $sessionID = session_id();

        // セッションに会員IDと会員名をセット
        $this->setSessionData( $result );

        $attr = array();
        if($update_login_time){
            // 最終ログイン日時
            $now = date('Y-m-d H:i:s');

            // セッションIDの更新
            $attr = array( 'mem_session_id' => $sessionID, 'last_login' => $now );
        } else {
            $attr = array( 'mem_session_id' => $sessionID );
        }
        $condition = array( "mem_id" => array( '=', $this->session->get('MEM_ID') ) );
        $retval = $dao->update( $attr, $condition );

        // DBエラー
        if($retval == $this->config->config['_DB_ERROR']) {
          $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." UPDATE mem_master FAILED ATTR:".print_r($attr,true)." COND:".print_r($condition,true));
          return Ethna::raiseError('システムエラーです。', E_CONSUMER_AUTH_UPDATESESSION_UPDATE);
        }

        // クッキー情報の成形
        $value = $result->mem_id . ':' . $sessionID;

        // 暗号用コンストラクタと暗号化
        $blowfish = new Crypt_Blowfish( $cryptKey );
        $EncryptValue = $blowfish->encrypt( $value );

        // 有効期限 30 日（自動ログインチェック無しの場合は期限無し）
        $timeout = ($update_auto_login) ? time() + 30 * 86400 : 0;

        // Cookie のセット
        setcookie( 'KjMember', $EncryptValue, $timeout, '/' );
    }

    /**
     * セッションデータ
     */
    function setSessionData( $result ) {
        //#{89} start maintance process: Luvina-company
        if($result->created != $result->pre_created) {
	        //#{89} end maintance process: Luvina-company
	        $this->session->set( 'MEM_NAME', $this->_htmlspecialchars_decode( $result->mem_name ) );
	        //#{89} start maintance process: Luvina-company
	        $this->session->set( 'MEM_TEMP', 0);
        } else {
            $this->session->set( 'MEM_TEMP', 1);
            //#{89} end maintance process: Luvina-company
            $this->session->set( 'MEM_NAME', null);
            //#{89} start maintance process: Luvina-company
        }
        //#{89} end maintance process: Luvina-company
        $this->session->set( 'MEM_MAIL', $result->mem_mail );
        //#774 start maintance process: Luvina-company
        $this->session->set('MEM_CAREER_ID', $result->mem_career_id);
        //#774 end maintance process: Luvina-company
    }
    
    // #89 start maintance process: Luvina-company
    
    /**
     * Check is mem templ 
     *
     * @return  1 is temp else return 0 
     */
    function isMemTempl() {
        return $this->session->get( 'MEM_TEMP');
    }
    // #89 end maintance process: Luvina-company
    /**
     * ログアウト
     */
    function logout( $memId ) {

        // MemMaster
        $dao = new MemMasterDao( $this->db, $this->backend );

        // 属性（冗長的だけど明確に）
        $attr = array(
            'mem_autologin_flg' => $this->config->config['_DB_FALSE']
        );

        // 条件（冗長的だけど明確に）
        $condition = array(
            'mem_id' => array(
                '=', $memId
            )
        );

        // 自動ログインフラグの変更
        $retval = $dao->update( $attr, $condition );

        // DBエラー
        if($retval == $this->config->config['_DB_ERROR']) {
            $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." UPDATE mem_master FAILED ATTR:".print_r($attr,true)." COND:".print_r($condition,true));
            return Ethna::raiseError('システムエラーです。', E_CONSUMER_LOGOUT);
        }

        // セッション破棄
        $this->session->destroy();

        // Cookie の削除
        setcookie( 'KjMember', '', time() - 3600, '/' );
        //#853 start maintance process: Luvina-company #comment:37
        //setcookie( 'ADV_VIEW_HISTORY', '', time() - 3600, '/' );
        //#853 start maintance process: Luvina-company
        //setcookie( 'ADV_VIEW_HISTORY_COUNT', '', time() - 3600, '/' );
        //$_COOKIE['ADV_VIEW_HISTORY_COUNT'] = 0;
        //#853 end maintance process: Luvina-company
        //#853 end maintance process: Luvina-company #comment:37

    }

    /**
     * 退会処理
     */
    //#588 start maintenance process: Luvina company
    function resign( $memId, $memMail, $isAdmin = false) {

        $this->logger->log(LOG_INFO, "Begin method:" . __METHOD__ . "() mem_id:".$memId);
        //#588 end maintenance process: Luvina company
        ///// 会員マスタ
        // MemMaster
        $memMasterDao = new MemMasterDao( $this->db, $this->backend );
        //#588 start maintenance process: Luvina company 
        $date = date('Y-m-d H:i:s');
        $modify = ($isAdmin) ? 0 : $memId;
        // 属性（冗長的だけど明確に）
        $attr = array(
            "delete_flg" => $this->config->config['_DB_TRUE']
            ,"modified" => $date
            ,"modified_by" => $modify
        );
        //#588 end maintenance process: Luvina company 
        // 条件（冗長的だけど明確に）
        $condition = array(
            "mem_id" => array(
                '=', $memId
            )
        );

        $this->logger->log(LOG_INFO,__METHOD__ . "() LINE:".__LINE__." UPDATE mem_master ATTR:".print_r($attr,true));

        // 自動ログインフラグの変更
        $memMasterUpdateRetval = $memMasterDao->update( $attr, $condition );

        ///// メルマガ受信者
        //メルマガオブジェクト
        $mgzUserDao = new MgzUserDao( $this->db, $this->backend );

        $params = array( $memMail, 3 );
        $mgzUserDao->searchMailmagazineUserId( &$mgzUser, $params );
        if( $mgzUser[0]['mgz_usr_id'] ){
            //メルマガ希望職種、希望エリアの削除
            $mgzUserOcpDao = new MgzUserOcpDao( $this->db, $this->backend );
            $mgzUserPrefDao = new MgzUserPrefDao( $this->db, $this->backend );
            $mgzUserCityDao = new MgzUserCityDao( $this->db, $this->backend );

            $this->logger->log(LOG_INFO,__METHOD__ . "() DELETE mgz_user_ocp,mgz_user_pref,mgz_user_city mgz_user_id:"
                                                       .$mgzUser[0]['mgz_usr_id']);

            $mgzUserOcpDeleteRetVal  = $mgzUserOcpDao->deleteMailmagazineWishOcp($mgzUser[0]['mgz_usr_id'],false);
            $mgzUserPrefDeleteRetVal = $mgzUserPrefDao->deleteMailmagazineWishAreaPref($mgzUser[0]['mgz_usr_id'],false);
            $mgzUserCityDeleteRetVal = $mgzUserCityDao->deleteMailmagazineWishAreaCity($mgzUser[0]['mgz_usr_id'],false);
        }
        // 条件
        $address = array($memMail);//mem_master.mem_mail
        $memInfo = $this->getMemberInfo();
        if(is_array($memInfo) && array_key_exists('mem_mobile_mail', $memInfo)
        && strlen($memInfo['mem_mobile_mail']) > 0) {
            $address[] = $memInfo['mem_mobile_mail'];//mem_master.mem_mobile_mail
        }

        $condition = array(
            array('mgz_usr_mail', 'IN', $address),
        );

        // 削除
        $this->logger->log(LOG_INFO,__METHOD__ . "() LINE:".__LINE__." DELETE mgz_user mgz_user_id:"
                                                       .$mgzUser[0]['mgz_usr_id']);
        $mgzUserDeleteRetval = $mgzUserDao->delete( $condition );

        if( $memMasterUpdateRetval   != $this->config->config['_DB_ERROR'] &&
            $mgzUserDeleteRetval     != $this->config->config['_DB_ERROR'] &&
            $mgzUserOcpDeleteRetVal  != $this->config->config['_DB_ERROR'] &&
            $mgzUserPrefDeleteRetVal != $this->config->config['_DB_ERROR'] &&
            $mgzUserCityDeleteRetVal != $this->config->config['_DB_ERROR'] ) {
            $mgzUserDao->commit();
        } else {
            $mgzUserDao->rollback();
            $this->logger->log(LOG_ERR,__METHOD__."() FAILED DB STATUS:".print_r(
                                                                   array("mem_master UPDATE COND:"    => array($attr, $condition),
                                                                         "mgz_user_ocp DELETE COND:"  => $mgzUser[0]['mgz_usr_id'],
                                                                         "mgz_user_pref DELETE COND:" => $mgzUser[0]['mgz_usr_id'],
                                                                         "mgz_user_city DELETE COND:" => $mgzUser[0]['mgz_usr_id'],
                                                                   ),true)
                              );
            return Ethna::raiseError('システムエラーです。', E_CONSUMER_RESIGN);
        }

        $this->logger->log(LOG_INFO,__METHOD__ . "() SESSION DESTROY");
        // セッション破棄（Cookie の削除）
        $this->session->destroy();

        $this->logger->log(LOG_INFO,__METHOD__ . "() DELETE COOKIE");
        // Cookie の削除
        setcookie( 'KjMember', '', time() - 3600, '/' );

        $this->logger->log(LOG_INFO, "END method:" . __METHOD__ . "()");

        return false;
    }

    /**
     * 新着メール数取得 - 応募管理(mem_apply)
     */
    function countNewMemApply() {

        // MemApply オブジェクト
        $memApplyDao = new MemApplyDao( $this->db, $this->backend, $this->session->get('MEM_ID') );

        // 新着メール数取得
        return $memApplyDao->countNewMemApply();
    }



    /**
     * メンバー新規登録の一連の流れ
     * @param applyFlg 応募時の会員登録かどうかを判定
     */
    //#773 start maintance process: Luvina-company 
    function addMember($applyFlg = false, $isApi = false) {
    //#773 start maintance process: Luvina-company 
        $this->logger->log(LOG_INFO, "Begin method:" . __METHOD__ . "()");

        $this->session->start();
        $sessionId = session_id();
        $now = date('Y-m-d H:i:s');
        $getForm = $this->af->getArray();
        //#{88} start maintance process: Luvina-company
        if($getForm['mem_temp'] == 1) {
            $getForm['mem_sex'] = '0';
            $getForm['mem_birthday_year'] = '1900';
            $getForm['mem_birthday_month'] = '1';
            $getForm['mem_birthday_day'] = '1';
        }
        //#{88} end maintance process: Luvina-company
        $attr = array();

        $dao = new MemMasterDao($this->db, $this->backend);
        $dao->getNextVal($result,'mem_master_mem_id_seq');
//      $memberID_seq = $result->nextval;
        $memberID_seq = $result;
        
        //#867 start maintenance process: Luvina company
        $getForm['mem_mail'] = $dao->_htmlspecialchars_decode($getForm['mem_mail']);
        //#867 end maintenance process: Luvina company

        // アフィリエイト用にmem_idを保持
        $this->session->set('AF_MEM_ID',$memberID_seq);

        $dao->begin();
        //まずメンバーマスターに書く
        $this->logger->log(LOG_INFO,__METHOD__ . "() LINE:".__LINE__." INSERT mem_master mem_id:".$memberID_seq);
        //#773 start maintance process: Luvina-company 
        //if( $dao->addMemMaster($memberID_seq, $now, $sessionId) == $this->config->config['_DB_OK'] ) {
        if( $dao->addMemMaster($memberID_seq, $now, $isApi) == $this->config->config['_DB_OK'] ) {
            //#773 end maintance process: Luvina-company
            $memSkilDao = new MemSkillDao($this->db, $this->backend);
            $attr = array();

            //次に保有資格を書く
            $this->logger->log(LOG_INFO,__METHOD__ . "() LINE:".__LINE__." INSERT mem_skill");
            if(is_array($getForm['mem_skl_id'])){
                foreach($getForm['mem_skl_id'] as $k => $v ) {
                    $attr = array('mem_id' => $memberID_seq, 'mem_skl_id' => $v );
                    if( $memSkilDao->create($attr) != $this->config->config['_DB_OK']) {
                        $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." INSERT mem_skill FAILED DATA:".print_r($attr,true));
                        $dao->rollback();
                        return Ethna::raiseNotice('登録エラー', E_CONSUMER_REGIST_ERROR);
                    }
                }
            }
            //メルマガ登録を書く
            $area_str = '';
            $ocp_str = '';
            if($getForm['mem_new_mgz_flg'] == 1) {
                if(!$applyFlg){

                    $mgzUserDao = new MgzUserDao($this->db, $this->backend);
                    
                    //Begin #214 by Luvina
                    $mgzUserOcpDao = new MgzUserOcpDao( $this->db, $this->backend );
                    $mgzUserPrefDao = new MgzUserPrefDao( $this->db, $this->backend );
                    $mgzUserCityDao = new MgzUserCityDao( $this->db, $this->backend );
                    //End #214 by Luvina
                    
                    //年齢丸める
                    $age = date("Y") - $getForm['mem_birthday_year'];
                    if( ($getForm['mem_birthday_month'] * 100 + $getForm['mem_birthday_day']) - (date("m") * 100 + date("d")) ) {
                        $age = $age-- ;
                    }
                    $generate = strval(floor($age / 10) * 10);

                    // http://svn.trustcity.co.jp/trac/sms-staff-xj01/ticket/965
                    // メルマガ条件のみ登録している人が会員登録する場合、登録済のメルマガ条件を引き継いで登録。
                    // ○方針（登録済の場合）
                    // mgz_user：会員登録時の入力値に対応するものを更新
                    // mgz_user_pref：会員登録時の都道府県が登録されていない場合のみ登録
                    // mgz_user_city：会員登録時の都道府県が登録されていない場合のみ登録
                    // mgz_user_ocp：会員登録時の取得資格に対応する職種で、未登録のもののみ登録

                    // メルマガ条件を取得
                    $params = array( $getForm['mem_mail'], 3 );
                    $ret = $mgzUserDao->searchMailmagazineUserId( $mgzUser, $params );
                    if($ret === $this->config->config['_DB_OK']){
                        $mgz_usr_id = $mgzUser[0]['mgz_usr_id'];
                        //3のメルマガ
                      //#214 Begin by Luvina
                        /*
                        $attr = array(
                            'mgz_usr_generation' => $generate,
                            'mgz_usr_sex' => $getForm['mem_sex'],
                            //'mgz_usr_date_registered' => $now,
                        );
                        $cond = array(
                            array('mgz_usr_id', '=', $mgz_usr_id),
                            array('mgz_id', '=', 3)
                        );
                        //#{103} start maintance process: Luvina-company
                        if($getForm['mem_temp'] == 1) {
                            $attr = array();
                            $this->createAttr($attr, $getForm['mem_frm_id']);   
                        }
                        //#{103} end maintance process: Luvina-company                        
                        //#{88} start maintance process: Luvina-company
                        //#{103} start comment out: Luvina-company
                        //if($getForm['mem_temp'] != 1) {
                        //#{103} end comment out: Luvina-company
                        //#{88} end maintance process: Luvina-company
                            if( $mgzUserDao->update($attr, $cond) !== $this->config->config['_DB_OK'] ) {
                                $mgzUserDao->rollback();
                                return Ethna::raiseNotice('登録エラー', E_CONSUMER_REGIST_ERROR);
                            }
                        //#{88} start maintance process: Luvina-company
                        //#{103} start comment out: Luvina-company
                        //}
                        //#{103} end comment out: Luvina-company
                        //#{88} end maintance process: Luvina-company
                        */
                        $this->logger->log(LOG_INFO,__METHOD__ . "() LINE:".__LINE__
                              ." DELETE mgz_user,mgz_user_ocp,mgz_user_city,mgz_user_pref");

                    	$ret = $mgzUserDao->deleteMailmagazine( $mgz_usr_id , false );
        				if($ret == $this->config->config['_DB_ERROR']) {
        					$mgzUserDao->rollback();
                            $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." DELETE mgz_user FAILED mgz_usr_id:".$mgz_usr_id);
                            //#773 start maintance process: Luvina-company
                            if($isApi) {
                                return Ethna::raiseError("DBエラーです。", E_CONSUMER_REGIST_ERROR);
                            } 
                            //#773 end maintance process: Luvina-company
            				return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
        				}       				
	                    
			        	$ret = $mgzUserOcpDao->deleteMailmagazineWishOcp( $mgz_usr_id , false );
				        if($ret == $this->config->config['_DB_ERROR']) {
				        	$mgzUserOcpDao->rollback();
                            $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." DELETE mgz_user_ocp FAILED mgz_usr_id:".$mgz_usr_id);
                            //#773 start maintance process: Luvina-company
                            if($isApi) {
                                return Ethna::raiseError("DBエラーです。", E_CONSUMER_REGIST_ERROR);
                            } 
                            //#773 end maintance process: Luvina-company
				            return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
				        }				        
			        	
			        	$mgzUserPrefDao->deleteMailmagazineWishAreaPref( $mgz_usr_id , false );
				        if($ret == $this->config->config['_DB_ERROR']) {
				        	$mgzUserPrefDao->rollback();
                            $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." DELETE mgz_user_pref FAILED mgz_usr_id:".$mgz_usr_id);
                            //#773 start maintance process: Luvina-company
                            if($isApi) {
                                return Ethna::raiseError("DBエラーです。", E_CONSUMER_REGIST_ERROR);
                            } 
                            //#773 end maintance process: Luvina-company
				            return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
				        }
				        
			        	$mgzUserCityDao->deleteMailmagazineWishAreaCity( $mgz_usr_id , false );
				        if($ret == $this->config->config['_DB_ERROR']) {
				        	$mgzUserCityDao->rollback();
                            $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." DELETE mgz_user_city FAILED mgz_usr_id:".$mgz_usr_id);
                            //#773 start maintance process: Luvina-company
                            if($isApi) {
                                return Ethna::raiseError("DBエラーです。", E_CONSUMER_REGIST_ERROR);
                            } 
                            //#773 end maintance process: Luvina-company
				            return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
				        }
                    //#214 End by Luvina
                    }
                     //#214 Begin by Luvina
                    //else{
                     //#214 End by Luvina
                        //3のメルマガ
                        $attr = array(
                            'mgz_usr_mail' => $getForm['mem_mail'],
                            'mgz_id' => '3',
                            'mgz_usr_generation' => $generate,
                            'mgz_usr_sex' => $getForm['mem_sex'],
                            //別テーブルに登録するので削除
                            //'mgz_usr_occupation' => $getForm['mem_mgz_ocp_id'],
                            //'mgz_usr_pref'       => $getForm['mem_mgz_pref_id'],
                            'mgz_usr_date_registered' => $now,
                        );
                        //#{103} start maintance process: Luvina-company
                        $this->createAttr($attr, $getForm['mem_frm_id']);
                        //#{103} end maintance process: Luvina-company
                        ////echo nl2br( print_r($attr ,true ) );
                        $this->logger->log(LOG_INFO,__METHOD__ . "() LINE:".__LINE__." INSERT mgz_user");
                        //#867 start maintenance process: Luvina company
                        if( $mgzUserDao->create( $attr, false) !== $this->config->config['_DB_OK'] ) {
                        //#867 end maintenance process: Luvina company
                            $this->logger->log(LOG_ERR,"DB ERROR INSERT mgz_user ".__METHOD__."() LINE:".__LINE__);
                            $mgzUserDao->rollback();
                            return Ethna::raiseNotice('登録エラー', E_CONSUMER_REGIST_ERROR);
                     }
                     $mgz_usr_id = $mgzUserDao->lastInsertId();
                   //#214 Begin by Luvina
                    /*
                    }
                    */
                   //#214 End by Luvina
                    // see http://svn.trustcity.co.jp/trac/sms-staff-xj01/ticket/957
                    // １．会員登録
                    // 都道府県：会員の都道府県
                    // 市区町村：全域
                    // 職種：取得資格に対応する職種
                    // ２．会員登録＋応募
                    // 応募処理内で行っているので、ここでは行わない

                    //3のメルマガの希望職種
                    $ocp_ids = parent::getOcpidBySklid($getForm['mem_skl_id']);
                    if(is_array($ocp_ids)){
                    	//#214 Begin commentout by Luvina
                        //$mgzUserOcpDao = new MgzUserOcpDao($this->db, $this->backend);
                        //#214 End commentout by Luvina
                        $this->logger->log(LOG_INFO,__METHOD__ . "() LINE:".__LINE__." INSERT mgz_user_ocp");
                        foreach($ocp_ids as $ocp){
                            if(!$mgzUserOcpDao->isRegisteredMailmagazineWishOcp($mgz_usr_id, $ocp['sys_ocp_id'])){
                                $attr = array(
                                    'mgz_usr_id' => $mgz_usr_id,
                                    'mgz_usr_ocp_id' => $ocp['sys_ocp_id']
                                );
                                if( $mgzUserOcpDao->create( $attr ) !== $this->config->config['_DB_OK'] ) {
                                    $dao->rollback();
                                    $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." INSERT mgz_user_ocp FAILED DATA:".print_r($attr,true));
                                    return Ethna::raiseNotice('登録エラー2', E_CONSUMER_REGIST_ERROR);
                                }
                            }
                        }
                    }

                    //3のメルマガの希望エリア（都道府県、市区町村）
                    //#214 Begin commentout by Luvina
                    //$mgzUserPrefDao = new MgzUserPrefDao($this->db, $this->backend);
                    //$mgzUserCityDao = new MgzUserCityDao($this->db, $this->backend);
                    //#214 End commentout by Luvina
                    if(!$mgzUserPrefDao->isRegisteredMailmagazineWishPref($mgz_usr_id, $getForm['mem_pref_id'])){
                        // 都道府県
                        //#214 begin fix bug 411
                        /*
                        $attr = array(
                            'mgz_usr_id' => $mgz_usr_id,
                            'mgz_usr_pref_id' => $getForm['mem_pref_id']
                        );
                        if( $mgzUserPrefDao->create( $attr ) !== $this->config->config['_DB_OK'] ) {
                            $mgzUserPrefDao->rollback();
                            return Ethna::raiseNotice('登録エラー3', E_CONSUMER_REGIST_ERROR);
                        }
                        */
                        //#214 end fix bug 411
                        // 市区町村
                        //#214 Begin 
                    	/*$attr = array(
	                        'mgz_usr_id' => $mgz_usr_id,
	                        'mgz_usr_city' => 0,
	                        'mgz_usr_pref_id' => $getForm['mem_pref_id']
	                    );
	                    if( $mgzUserCityDao->create( $attr ) !== $this->config->config['_DB_OK'] ) {
	                       	$mgzUserCityDao->rollback();
	                        return Ethna::raiseNotice('登録エラー4', E_CONSUMER_REGIST_ERROR);
	                    }*/
                        $mgzSendVolume = $this->validateMgzSendVolume();
                        
						$advMasterDao = new AdvMasterDao($this->db, $this->backend);
						
						$countProject = 0;
						$rs = $advMasterDao->countProjectPublish($countProject, $getForm['mem_pref_id']);
						
						if($rs == $this->config->config['_DB_ERROR']) {
                    		$advMasterDao->rollback();
                            $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." count Project FAILED pref_id:".$getForm['mem_pref_id']);
	                        return Ethna::raiseNotice('DBエラーです。', E_CONSUMER_REGIST_ERROR);
	                    }
                        //#657 start maintenance process: Luvina-company
                        /*
                        if($countProject < $mgzSendVolume) {
                        */
                        //#657 end maintenance process: Luvina-company
	                        $attr = array(
	                            'mgz_usr_id' => $mgz_usr_id,
                                //#657 start maintenance process: Luvina-company
                                    //#657 start maintance process: Luvina-company : 【Bug ID: 1784】 #comment:7 
	                            //'mgz_usr_city' => ($countProject < $mgzSendVolume) ? 0 : $getForm['mem_city_id'],
	                            'mgz_usr_city' => $getForm['mem_city_id'],
	                            //#657 end maintance process: Luvina-company : 【Bug ID: 1784】 #comment:7
                                //#657 end maintenance process: Luvina-company
	                            'mgz_usr_pref_id' => $getForm['mem_pref_id']
	                        );
                            $this->logger->log(LOG_INFO,__METHOD__ . "() LINE:".__LINE__." INSERT mgz_user_city");
	                        if( $mgzUserCityDao->create( $attr ) !== $this->config->config['_DB_OK'] ) {
	                            $mgzUserCityDao->rollback();
                                $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." INSERT mgz_user_city FAILED DATA:".print_r($attr,true));
	                            return Ethna::raiseNotice('登録エラー4', E_CONSUMER_REGIST_ERROR);
	                        }
	                        //#214 begin fix bug 411
	                        $attr = array(
	                            'mgz_usr_id' => $mgz_usr_id,
	                            'mgz_usr_pref_id' => $getForm['mem_pref_id']
	                        );
                            $this->logger->log(LOG_INFO,__METHOD__ . "() LINE:".__LINE__." INSERT mgz_user_pref");
	                        if( $mgzUserPrefDao->create( $attr ) !== $this->config->config['_DB_OK'] ) {
	                            $mgzUserPrefDao->rollback();
                                $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." INSERT mgz_user_pref FAILED DATA:".print_r($attr,true));
	                            return Ethna::raiseNotice('登録エラー3', E_CONSUMER_REGIST_ERROR);
	                        }
	                        //#214 end fix bug 411
                        //#657 start maintenance process: Luvina-company
                        /*
                        } else {
                        	
                        	$nearAreaMasterDao = new NearAreaMasterDao($this->db, $this->backend);
                        	
                        	$aryNearArea = array();
                        	$rs = $nearAreaMasterDao->getNearAreaUsrCity($aryNearArea, $getForm['mem_pref_id'], $getForm['mem_city_id']);
	                        if( $rs == $this->config->config['_DB_ERROR'] ) {
	                            $nearAreaMasterDao->rollback();
                                $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__.
                                         " SELECT near_area_master FAILED COND:".print_r(array($getForm['mem_pref_id'],$getForm['mem_city_id']),true));
	                            return Ethna::raiseNotice('DBエラーです。', E_CONSUMER_REGIST_ERROR);
	                        }
	                        //#214 begin fix bug 411
	                        $sysAreCityDao = new SysAreCityDao($this->db, $this->backend);
		                    $aryPref = array();
		                    //#214 end fix bug 411
		                    
		                    //#228 begin by Luvina
		                    $aryNear = array();
		                    //#228 end by Luvina
		                    
                        	foreach($aryNearArea as $nearAreaCityId) {
                        		//#214 begin fix bug 411
                        		$ret= $sysAreCityDao -> getSysAreCityPref($nearAreaPrefId, $nearAreaCityId);
	                        	if( $ret == $this->config->config['_DB_ERROR'] ) {
		                            $sysAreCityDao->rollback();
                                    $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__.
                                         " SELECT sys_are_city FAILED COND:".print_r(array($nearAreaPrefId,$nearAreaCityId),true));
		                            return Ethna::raiseNotice('DBエラーです。', E_CONSUMER_REGIST_ERROR);
		                        }
		                        //#228 begin by Luvina
		                        if(!$nearAreaPrefId) {
			                        $nearAreaPrefId = (int)substr($nearAreaCityId, 0, 2);
			                        $sysPrefDao = new SysPrefectureDao($this->db, $this->backend);
			                        if($sysPrefDao -> normalCount(array(array('sys_pref_id', '=', $nearAreaPrefId))) != 1) {
			                        	continue;
			                        }
			                        if(!isset($aryNear[$nearAreaPrefId])) {
			                        	$aryNear[$nearAreaPrefId][0] = 0;
			                        }
		                        } else {		                        	
		                        	$aryNear[$nearAreaPrefId][$nearAreaCityId] = $nearAreaCityId;
		                        	if(isset($aryNear[$nearAreaPrefId][0])) {
			                        	unset($aryNear[$nearAreaPrefId][0]);
			                        }
		                        }
		                        /*
		                        if(!in_array($nearAreaPrefId, $aryPref)) {
		                        	array_push($aryPref, $nearAreaPrefId);
		                        }
		                        //#214 end fix bug 411
	                        	$attr = array(
		                            'mgz_usr_id' => $mgz_usr_id,
		                            'mgz_usr_city' => $nearAreaCityId,
		                            'mgz_usr_pref_id' => $nearAreaPrefId
		                        );
		                        *//*
                        		//#228 end by Luvina		                        
                        	}
                        		                       	
                        	//#228 begin by Luvina
                        	foreach ($aryNear as $key => $value) {
                        		$attr = array(
		                            'mgz_usr_id' => $mgz_usr_id,
		                            'mgz_usr_pref_id' => $key
		                        );
                                $this->logger->log(LOG_INFO,__METHOD__ . "() LINE:".__LINE__." INSERT mgz_user_pref By near_area_master");
		                        if( $mgzUserPrefDao->create( $attr ) !== $this->config->config['_DB_OK'] ) {
		                            $mgzUserPrefDao->rollback();
                                    $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__.
                                         " INSERT mgz_user_pref FAILED DATA:".print_r($attr,true));
		                            return Ethna::raiseNotice('登録エラー3', E_CONSUMER_REGIST_ERROR);
		                        }	
                                $this->logger->log(LOG_INFO,__METHOD__ . "() LINE:".__LINE__." INSERT mgz_user_city By near_area_master");
                        		foreach ($value as $city) {
                        			$attr = array(
		                            	'mgz_usr_id' => $mgz_usr_id,
		                            	'mgz_usr_city' => $city,
		                            	'mgz_usr_pref_id' => $key
		                        	);
	                        		if( $mgzUserCityDao->create( $attr ) !== $this->config->config['_DB_OK'] ) {
			                            $mgzUserCityDao->rollback();
                                        $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__.
                                         " INSERT mgz_user_city FAILED DATA:".print_r($attr,true));
			                            return Ethna::raiseNotice('登録エラー4', E_CONSUMER_REGIST_ERROR);
			                        }
                        		}
                        	}
                            */
                            //#657 end maintenance process: Luvina-company
                        	/*
                        	//#214 begin fix bug 411
                        	if(count($aryPref) > 0) {
	                        	foreach ($aryPref as $prefId) {
		                        	$attr = array(
		                            	'mgz_usr_id' => $mgz_usr_id,
		                            	'mgz_usr_pref_id' => $prefId
		                        	);
		                        	if( $mgzUserPrefDao->create( $attr ) !== $this->config->config['_DB_OK'] ) {
		                            	$mgzUserPrefDao->rollback();
		                            	return Ethna::raiseNotice('登録エラー3', E_CONSUMER_REGIST_ERROR);
		                        	}
	                        	}
                        	}
                        	//#214 end fix bug 411
                        	*/
                        	//#228 end by Luvina
                        //#657 start maintenance process: Luvina-company
                        //}
                        //#657 end maintenance process: Luvina-company
                        //#214 End 
                    }

                    // メルマガ条件を取得
                    $params = array( $getForm['mem_mail'], 2 );
                    $ret = $mgzUserDao->searchMailmagazineUserId( $mgzUser, $params );
                    
                    //Begin #214 by Luvina 
                    /*if($ret !== $this->config->config['_DB_OK']){
                        //2のメルマガ
                        $attr = array(
                            mgz_usr_mail => $getForm['mem_mail'],
                            mgz_id => '2',
                            mgz_usr_date_registered => $now,
                        );
                        ////echo nl2br( print_r($attr ,true ) );
                        if( $mgzUserDao->create( $attr ) !== $this->config->config['_DB_OK'] ) {
                            $dao->rollback();
                            return Ethna::raiseNotice('登録エラー', E_CONSUMER_REGIST_ERROR);
                        }
                    }*/
                	if($ret == $this->config->config['_DB_OK']){
                        //2のメルマガ
                        $mgz_usr_id = $mgzUser[0]['mgz_usr_id'];
                        $ret = $mgzUserDao->deleteMailmagazine( $mgz_usr_id , false );
                        if($ret == $this->config->config['_DB_ERROR']) {
                            $mgzUserDao->rollback();
                            $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__.
                                         " DELETE mgz_user FAILED mgz_usr_id:".$mgz_usr_id);
                            //#773 start maintance process: Luvina-company
                            if($isApi) {
                                return Ethna::raiseError("DBエラーです。", E_CONSUMER_REGIST_ERROR);
                            } 
                            //#773 end maintance process: Luvina-company
                            return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
                        }
                    }else if($ret == $this->config->config['_DB_ERROR']){
                        //DBエラー処理が無かったので追加 ADD SMS 2013/06/21
                        $mgzUserDao->rollback();
                        $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__.
                                         " SELECT mgz_user FAILED mgz_usr:".$mgzUser);
                            //#773 start maintance process: Luvina-company
                            if($isApi) {
                                return Ethna::raiseError("DBエラーです。", E_CONSUMER_REGIST_ERROR);
                            } 
                            //#773 end maintance process: Luvina-company
                        return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
                    }

                    $attr = array(
                            mgz_usr_mail => $getForm['mem_mail'],
                            mgz_id => '2',
                            mgz_usr_date_registered => $now,
                    );
                    $this->logger->log(LOG_INFO,__METHOD__."() LINE:".__LINE__." INSERT mgz_user mgz_id=2");
                     ////echo nl2br( print_r($attr ,true ) );
                    //#867 start maintenance process: Luvina company
                    if( $mgzUserDao->create( $attr, false) !== $this->config->config['_DB_OK'] ) {
                    //#867 end maintenance process: Luvina company
                            $dao->rollback();
                            $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." INSERT mgz_user FAILED");
                            return Ethna::raiseNotice('登録エラー', E_CONSUMER_REGIST_ERROR);
                    }
                    //End #214 by Luvina
                }
/**/
            }

            $dao->commit();
            
            // http://svn.trustcity.co.jp/trac/sms-staff-xj01/ticket/1735
            // 処理実行時にCV完了値を更新するため、フラグを立てる。
            /* @var $cnvMgr Kj_ConversionManager */
            /*
            //#{88} start commentout by Luvina
            $cnvMgr = $this->backend->getManager('Conversion');
            $cnvMgr->setCvUpdateFlg();
            //#{88} end commnet out by luvina
            */
            //#{88} start maintance process: Luvina-company
            //#96 begin
            //if($getForm['mem_temp'] != 1) {
            //#96 end
                // if not user temp clear conversion flg
                /* @var $cnvMgr Kj_ConversionManager */
                $cnvMgr = $this->backend->getManager('Conversion');
                $cnvMgr->setCvUpdateFlg();
            //#96 begin
            //}
            //#96 end
            //#{88} end maintance process: Luvina-company

            //確認メールを送信する
            //メールに貼り付ける 類似広告を取得する
            $publicMgr = $this->backend->getManager('Public');
            if ($publicMgr->countSimilarAdvs($getForm)) {
                $similarAdvs = $publicMgr->getSimilarAdvs($getForm);
            }
            
            //#{88} start maintance process: Luvina-company
            $nameFile = $this->config->get('NAME_FILE');
            
            if($getForm['mem_temp']==1) { 
                $entryNameFile = $nameFile['consumer_entry_pre_consumer'];
                $magazineNameFile = $nameFile['public_magazine_regist_pre_consumer'];
            } else {
                $entryNameFile = $nameFile['consumer_entry_consumer'];
                $magazineNameFile = $nameFile['consumer_mypage_settings_entmgz_consumer'];
            }
            //#{88} end maintance process: Luvina-company

            //会員にメール
            $this->logger->log(LOG_INFO,__METHOD__."() LINE:".__LINE__." SEND MAIL >> MEMBER REGIST OK");
            $ethna_mail =& new Kj_MailSender($this->backend);
            $ethna_mail->send(
                $getForm['mem_mail'],
                //#{88} start maintance process: Luvina-company
                $entryNameFile,
                //#{88} end maintance process: Luvina-company
                array(
                    'mem_name' => $getForm['mem_name'],
                    //'from' => 'info@kaigojob.com',
                    'login_url' => $this->config->config['url'] . '/?act=consumer_mypage_index' ,
                    'password_remind_url' => $this->config->config['url'] . '/?act=consumer_req_pass',
                    'simAdvs' => $similarAdvs,
                )
            );

            //メルマガ会員チェックの場合はメルマガ登録案内
            if($getForm['mem_new_mgz_flg'] == 1) {
                if(!$applyFlg){
                    // 都道府県、市区町村の文字列を取得（元々登録されていた内容を含む）
                    $mgzWishOcpIds = $this->getMgzUserOcpList( $mgz_usr_id );
                    $this->getWishOcpStringArray( $mgzWishOcpIds, $wishOcpList );
                    $mgzWishAreaCityIds = $this->getMgzUserWishAreaCityList( $mgz_usr_id );
                    $wish_area_city_list = array();
                    foreach( $mgzWishAreaCityIds as $_key => $_val ){
                        $tmp = "";
                        $tmp .= $_key.",";
                        $city_ids = implode(",",$_val);
                        $tmp .= $city_ids;

                        array_push($wish_area_city_list,$tmp);
                    }
                    $this->getWishAreaCityStringArrayList($wish_area_city_list, $wishAreaCityList);

                    //#{88} start maintance process: Luvina-company
                    if($getForm['mem_temp'] == 1) { 
                        $strWishOcpList = implode(",", $wishOcpList);
                        $aryPrefName = array();
                        foreach ($wishAreaCityList as $_key => $_val) {
                            $prefName = "";
                            $prefName .= $_key.' ： ';
                            $prefName .= implode("、", $_val);
                            array_push($aryPrefName, $prefName);
                        }
                    }
                    //#{88} end maintance process: Luvina-company
                    //#{103} start maintance process: Luvina-company
                    $dao = new SysFormDao($this->db, $this->backend);
                    $aryMemFrmId = $getForm['mem_frm_id'];
                    if(is_array($aryMemFrmId)) {
                        $cnd = array();
                        sort($aryMemFrmId);
                        $cnd[] = array('sys_frm_id', "IN", $aryMemFrmId);
                        $ret = $dao->getList($resultSysForm, $cnd);
                        if($ret == $this->config->config['_DB_ERROR']) {
                            $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__.
                                         " SELECT sys_form FAILED COND:".$cnd);
                            //#773 start maintance process: Luvina-company
                            if($isApi) {
                                return Ethna::raiseError("DBエラーです。", E_CONSUMER_REGIST_ERROR);
                            } 
                            //#773 end maintance process: Luvina-company
                            return Ethna::raiseError('システムエラーです。', E_DB_ERROR);
                        }
                        $resultSysForm = Kj_Util::arrayIndex($resultSysForm, 'sys_frm_id', 'sys_frm_name');
                    }
                    // #601 start maintenance process: luvina company
                    $aryPref = $aryCity = array();
                    if(is_array($mgzWishAreaCityIds) && count($mgzWishAreaCityIds) > 0) {
                        foreach ($mgzWishAreaCityIds AS $key => $val) {
                            if(is_array($val) && count($val) > 0 && $val[0] > 0) {
                                $aryCity = array_merge($aryCity, $val);
                            } else {
                                $aryPref[] = $key;
                            }
                        }
                    }
                    
                    $similarAdvsMagazine = $this->makeSimilarAdvsForMagazine($getForm['mem_mail'], $mgzWishOcpIds, $aryMemFrmId, $aryPref, $aryCity);
                    // #601 end maintenance process: luvina company
                    //#{103} end maintance process: Luvina-company
                    $this->logger->log(LOG_INFO,__METHOD__."() LINE:".__LINE__." SEND MAIL >> MAIL MAGAZINE REGIST OK");
                    $ethna_mail->send(
                        $getForm['mem_mail'],
                        //#{88} start maintance process: Luvina-company
                        $magazineNameFile,
                        //#{88} end maintance process: Luvina-company
                        array(
                        'mem_name'            => $getForm['mem_name'],
                        'wishOcpList'         => $wishOcpList,
                        //#{88} start maintance process: Luvina-company
                        'mgz_usr_occupation_name' => $strWishOcpList,
                        'mgz_usr_pref_name'   => $aryPrefName,
                        //#{103} start maintance process: Luvina-company
                        'mgz_usr_frm_name'   => $resultSysForm,
                        //#{103} end maintance process: Luvina-company
                        //#{88} end maintance process: Luvina-company
                        'wishAreaCityList'    => $wishAreaCityList,
                        'settings_url'        => $this->config->config['url'] . '/?act=consumer_mypage_settings_index',
                        'password_remind_url' => $this->config->config['url'] . '/?act=consumer_reqpass',
                        // #601 start maintenance process: luvina company
                        'simAdvsMagazine' => $similarAdvsMagazine,
                        // #601 end maintenance process: luvina company
                        )
                    );
                }
            }

            //#155: start modify by Luvina
            /*
            //管理者にメール
            $ethna_mail->send(
                $this->config->config['sms_admin_mail'] ,
                'Consumer_Entry_admin.txt',
                array(
                    //'from' => 'info@kaigojob.com',
                    'mem_id' => sprintf("%07d",$memberID_seq),
                )
            );
            */
            //#155: End modify by Luvina

            // モバイルカイゴジョブのURL送信
            if($getForm['accept_mkj_url'] && strlen($getForm['mem_mobile_mail']) > 0) {
                $ethna_mail->send(
                     $getForm['mem_mobile_mail'],
                     'Consumer_Entry_send_mkj_url.txt',
                     array(
                        'mem_name' => $getForm['mem_name'],
                        'mobile_home_url' => $this->config->get('mobile_home_url')
                     )
                );
            }

            //Cookieの発行
            $this->_mySettingCookie($memberID_seq, $sessionId);


        } else {
            $dao->rollback();
            $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__.
                          " INSERT mem_master FAILED DATA:".print_r(array($memberID_seq, $now, $sessionId),true));
            return Ethna::raiseNotice('登録エラー', E_CONSUMER_REGIST_ERROR);
        }

        $this->logger->log(LOG_INFO, "END method:" . __METHOD__ . "()");

        return false ;
    }
    //#214 begin
    private function validateMgzSendVolume() {
        $mgzSendVolume = $this->config->config['MGZ_SEND_VOLUME'];
		if(!preg_match("/^[0-9]+$/", (string) $mgzSendVolume)) {
		    $mgzSendVolume = self::MGZ_SEND_VOLUME_DEFAULT;
		}
		
		return (int)$mgzSendVolume;
    }
    //#214 end
    
    // #{103} start maintance process: Luvina-company
    /**
     * create attr for insert and update
     *
     * @param array $attr array attr 
     * @param array $mgzUsrFrmId array id of mgz_usr_frm_id
     * @param array $mgzUsrSrvId array id of mgz_usr_srv_id
     */
    function createAttr(&$attr, $mgzUsrFrmId = '', $mgzUsrSrvId = '') {
        
        if(is_array($mgzUsrFrmId)) {
            $attr['mgz_usr_frm_id'] = "|" . implode("|", $mgzUsrFrmId) . "|";
        }
        
        if(is_array($mgzUsrSrvId)) {
            $attr['mgz_usr_srv_id'] = "|" . implode("|", $mgzUsrSrvId) . "|";
        }
        
    }
    // #{103} end maintance process: Luvina-company

    /**
     * ログインしてない時コンシューマーのクッキーセット用
     *
     */
    function _mySettingCookie($Id,$sessionID){
        // 暗号化用キー
        $cryptKey = $this->config->get('crypt_key');
        // セッションに会員IDをセット
        $this->session->set('MEM_ID',$Id);
        // クッキー情報の成形
        $value = $Id . ':' . $sessionID;
        // 暗号用コンストラクタと暗号化
        $blowfish = new Crypt_Blowfish( $cryptKey );
        $EncryptValue = $blowfish->encrypt( $value );
        // 有効期限 30 日
        $timeout = time() + 30 * 86400;
        // Cookie のセット
        setcookie( 'KjMember', $EncryptValue, $timeout, '/' );
    }

    /**
     * パスワードリマインダ
     * 誕生日とメルアドで送るよ
     *
     */
    function sendPass(){
        $getForm = $this->af->getArray();
        //#867 start maintenance process: Luvina company
        $getForm['mem_mail'] = parent::_htmlspecialchars_decode($getForm['mem_mail']);
        //#867 end maintenance process: Luvina company
        $dao = new MemMasterDao($this->db, $this->backend);
        // see http://svn.trustcity.co.jp/trac/sms-staff-xj01/ticket/754
        //#{86} start maintance process: Luvina-company
        //$mem_birthday = date( "Y-m-d", mktime(0, 0, 0, $getForm['mem_birthday_month'], $getForm['mem_birthday_day'], $getForm['mem_birthday_year']) );
        //#{86} end maintance process: Luvina-company
        
        $condition = array(
            array( 'mem_mail', '=', $getForm['mem_mail']),
            //#{86} start maintance process: Luvina-company
           // array( 'mem_birthday', '=', $mem_birthday),
           //#{86} end maintance process: Luvina-company
            array( 'delete_flg', '=', $this->config->config['_DB_FALSE'] )
        );
        //
        //echo nl2br( print_r($condition ,true ) );
        $return = $dao->getList($member, $condition, false, true );
        switch($return) {
        case $this->config->config['_DB_OK'] :
            //仮パスワード発行
            $tmp_pass = substr(Ethna_Util::getRandom(), 0, 10);
            //mem_masterに仮パスワードと現在時刻をupdate
            $now = date( "Y-m-d H:i:s",time() );
            $attr = array(
                'mem_tmp_password' => $tmp_pass,
                'mem_tmp_password_issuetime' => $now
            );
            $condition = array(
                array( 'mem_id', '=', $member->mem_id),
            );
            if( $dao->update( $attr, $condition ) !== $this->config->config['_DB_OK']) {
                return Ethna::raiseNotice('DBERROR', E_DB_ERROR);
            }
            //TODO
            //#599 start maintance process: Luvina-company 
            $memId = $member->mem_id;
	        $memSkill = $this->getMemSkillbyAryMemId(array($memId));
	        $ocps = $ocp = array();
	        $ocps = $this->getOcpidBySklid($memSkill[$memId]);
	        foreach ($ocps as $v) {
	            $ocp[] = $v['sys_ocp_id'];
	        }
            $getForm['similar_pref_id'] = array($member->mem_pref_id);
            $getForm['similar_city_id'] = array($member->mem_city_id);
            $getForm['similar_ocp_id'] = $ocp;
            $getForm['similar_from'] = 'entry';
            /* @var $publicMgr Kj_PublicManager */
	        $publicMgr = $this->backend->getManager('Public');
	        $similarAdvs = $publicMgr->getSimilarAdvs($getForm);
	        $similarAdvs = ($similarAdvs) ? $similarAdvs : array();
	        //#599 end maintance process: Luvina-company
            //メール送る
            $ethna_mail =& new Kj_MailSender($this->backend);
            $ethna_mail->send(
                $member->mem_mail,
                'Consumer_SendPass_consumer.txt',
                array(
                    //'from' => 'info@kaigojob.com',
                    'mem_name' => $member->mem_name,
                    'mem_tmp_password' => $tmp_pass,
                    'remind_pass_login_url' => $this->config->config['url'] . '/?act=consumer_remindpasslogin',
                    //#599 start maintance process: Luvina-company
                    'simAdvs'   => $similarAdvs,
                    //#599 end maintance process: Luvina-company
                )
            );
            //

            return false ;
            break;
        case $this->config->config['_DB_EMPTY'] :
            return Ethna::raiseNotice('該当するユーザーが見つかりませんでした', E_CONSUMER_PASSWORD_REMINDER_MEMBER_NOT_FOUND);
            break ;
        default :
            $this->logger->log(LOG_ERR,__METHOD__."() LINE:".__LINE__." FAILED ");
            return Ethna::raiseNotice('ERROR', E_CONSUMER_PASSWORD_REMINDER_DB_ERROR);
            break ;
        }
    }

    /**
     * 仮パスワード認証
     *
     */
    function tmpPassAuth(){
        $getForm = $this->af->getArray();
        $dao = new MemMasterDao($this->db, $this->backend);
        $condition = array(
                //#867 start maintenance process: Luvina company
            array('mem_mail','=', $dao->_htmlspecialchars_decode($getForm['mem_mail'] )),
                //#867 end maintenance process: Luvina company
            array('mem_tmp_password','=', $getForm['mem_tmp_password'] ),
        );
        $return = $dao->getList($member, $condition, false, true) ;
        switch($return) {
        case $this->config->config['_DB_OK'] :
            /*
            echo nl2br( print_r($member ,true ) );
            echo "time() : " . time() . "<br />" ;
            echo "issuetime : " . date( "Y-m-d H:i:s",time() ) . "<br />" ;
            echo "24hour : " . 60 * 60 * 24 . "<br />" ;
            echo "<hr />" ;
            */

            if( ( time() - strtotime($member->mem_tmp_password_issuetime) ) > ( 60 * 60 * 24) ) {
                return Ethna::raiseNotice('仮パスワードが期限切れです', E_CUSTOMER_PASSWORD_REMINDER_TIMEOUT);
            }
            //session認証OKなことを書く。
            $this->session->remove('TMP_MEM');
            $this->session->set( 'TMP_MEM_ID', $member->mem_id );
            //
            return false ;
            break;
        case $this->config->config['_DB_EMPTY'] :
            $this->logger->log(LOG_INFO,"AUTH FAILED ".__METHOD__."() LINE:".__LINE__);
            return Ethna::raiseNotice('認証に失敗しました', E_CUSTOMER_PASSWORD_REMINDER_MEMBER_NOT_FOUND);
            break ;
        default :
            $this->logger->log(LOG_ERR,"DB ERROR ".__METHOD__."() LINE:".__LINE__);
            return Ethna::raiseNotice('ERROR', E_DB_ERROR);
            break ;
        }
    }

    /**
     * パスワードの更新
     *
     */
    function updatePassword(){
        $getForm = $this->af->getArray();
        $dao = new MemMasterDao($this->db, $this->backend);
        $condition = array(
            array('mem_id','=', $this->session->get('TMP_MEM_ID') )
        );
        $attr = array(
            'mem_password' => $getForm['mem_password'],
            'mem_tmp_password' => NULL,
            'mem_tmp_password_issuetime' => NULL,
        );

        $dao->begin();
        if( $dao->update( $attr, $condition ) !== $this->config->config['_DB_OK'] ) {
            $this->logger->log(LOG_ERR,"UPDATE mem_maseter FAILED ".__METHOD__."() LINE:".__LINE__);
            $dao->rollback();
            return Ethna::raiseNotice('ERROR', E_CONSUMER_PASSWORD_REMINDER_DB_ERROR);
        }
        if( $dao->getList( $member, $condition , false , true ) !== $this->config->config['_DB_OK'] ) {
            $this->logger->log(LOG_ERR,"SELECT mem_maseter FAILED ".__METHOD__."() LINE:".__LINE__);
            $dao->rollback();
            return Ethna::raiseNotice('ERROR', E_CONSUMER_PASSWORD_REMINDER_DB_ERROR);
        }
        $dao->commit();

        // セッションIDの取得
        $this->session->set( 'MEM_ID', $this->session->get('TMP_MEM_ID') );
        //DBのアップデートとクッキーの書き込み
        $this->updateSession();

        //sessionの破棄
        $this->session->remove('TMP_MEM_ID');

        return false ;
    }

    /**
     * 引数のメールアドレスとパスワードで mem_master を検索して、
     * mem_master.mem_id と セッションの MEM_ID とが一致するかチェックする。
     *
     * @param string $mail メールアドレス
     * @param string $passwd パスワード
     */
    public function isLoggingIn($mail, $passwd)
    {
        $dao = new MemMasterDao($this->db, $this->backend);
        $cnd = array(
                    array('mem_mail', '=', $mail),
                    array('mem_password', '=', $passwd),
                    array('delete_flg', '=', $this->config->config['_DB_FALSE'])
               );
        $ret = $dao->getList($result, $cnd, false, true);
        if($ret == $this->config->config['_DB_ERROR']) {
            $this->logger->log(LOG_ERR,"DB ERROR ".__METHOD__."() LINE:".__LINE__);
            return Ethna::raiseError('DB error occurred.', E_DB_ERROR);
        } else if($ret == $this->config->config['_DB_EMPTY']) {
            $this->logger->log(LOG_ERR,"DB_EMPTY ".__METHOD__."() LINE:".__LINE__);
            return Ethna::raiseError('メールアドレスまたはパスワードが違います。');
        }

        $_mem_id = $result->mem_id;
        $mem_id  = $this->session->get('MEM_ID');
        if(strcmp($_mem_id, $mem_id) === 0) {
            return true;
        } else {
            $this->logger->log(LOG_WARNING, "他人になりすましています。なりすましているユーザ[ID={$mem_id}]/なりすまされているユーザ[ID={$_mem_id}]");
            return Ethna::raiseError('メールアドレスまたはパスワードが違います。');
        }

    }

    ///////////////////////////////////////////////////////////
    //以下のメソッドはクビ

    /**
     * 日付けの妥当性チェック
     * plugin化したからクビ
     *
     */
    function checkDate( $name = "年月日" ) {
        $getForm = $this->af->getArray();
        if( checkdate($getForm['mem_birthday_month'],$getForm['mem_birthday_day'], $getForm['mem_birthday_year'] ) == false ) {
            return Ethna::raiseNotice($name."を正しく入力して下さい", E_CONSUMER_REGIST_ERROR);
        }
        return false ;
    }

    /**
     * 一致のチェック
     * plugin化したからクビ
     *
     */
    function checkConfirm( $var1, $var2, $error_message ) {
        $getForm = $this->af->getArray();
        if( $getForm[$var1] != $getForm[$var2] ){
            return Ethna::raiseNotice($error_message, E_CONSUMER_REGIST_ERROR);
        }
        return false ;
    }

    /**
     * ユニークかのチェック
     * plugin化したからクビ
     *
     */
    function checkUnique($val, $error_message) {
        $dao = new MemMasterDao($this->db, $this->backend);
        $condition = array($val => array("=",$this->af->get($val)));
        if( $dao->count($condition) ) {
            return Ethna::raiseNotice($error_message, E_CONSUMER_REGIST_ERROR);
        }
        return false ;
    }

    /**
     * どっちか入力してるかチェック
     * plugin化したからクビ
     *
     */
    function checkGroupRequired( $var1, $var2, $error_message ) {
        $getForm = $this->af->getArray();
        if( empty($getForm[$var1]) && empty($getForm[$var2]) ){
            return Ethna::raiseNotice($error_message, E_CONSUMER_REGIST_ERROR);
        }
        return false ;
    }

    /**
     * 都道府県一覧のarrayを返すYO
     * viewの方を使うはずだからクビ
     * @return array
     */
    function getSysPrefecture(&$prefList) {
        require_once('dao/SysPrefectureDao.php');
        $dao = new SysPrefectureDao($this->db, $this->backend);
        if ( $dao->getList($result) == $this->config->config['_DB_OK'] ) {
            $prefList = array();
            for($i=0; count($result) > $i; $i++) {
                $prefList[] = $result[$i]['sys_pref_namefull'];
            }
            return false ;
        } else {
            return Ethna::raiseNotice("県名リストの取得に失敗しました", E_CONSUMER_REGIST_ERROR);
        }
        //echo nl2br( print_r($result ,true ) );
    }

    /**
     * region code
     * $region = getenv("HTTP_X_SFPT_REGION");
     * region code から sys_pref_id を取得
     * @param
     * @return int セレクトした結果
     */
    function getRegionCode( $regionCode = false ) {

         $surfPointMgr = $this->backend->getManager('SurfPoint');
         $cd           = $surfPointMgr->getRegionCode($this->config->get('sfpt_test_region_code'));
         
        return $cd;
    }

        /**
         * htmlspecialchars_decode
         * (PHP 5 >= 5.1.0) orz...
         */
        function _htmlspecialchars_decode( $char ) {
        if ( $char === true || $char === false ) {
            return $char;
        }
                $char = str_replace( '&amp;', '&', $char);
                $char = str_replace( '&lt;', '<', $char);
                $char = str_replace( '&gt;', '>', $char);
                $char = str_replace( '&quot;', '"', $char);
                $char = str_replace( '&#039;', '\'', $char);
                return $char;
        }

        function getActionForms(){
            return $this->af->getArray();
        }

    function updateLastLogin($memId, $timestamp = null)
    {
        if(is_null($timestamp)) $timestamp = date("Y-m-d H:i:s");
        $attr = array("last_login" => $timestamp);
        $condition = array(array("mem_id", "=", $memId));
        $dao = new MemMasterDao($this->db, $this->backend);
        $dao->begin();
        if($dao->update($attr, $condition) == $this->config->get("_DB_OK")) {
            $dao->commit();
            return true;
        } else {
            $dao->rollback();
            $this->logger->log(LOG_ERR,__METHOD__."() FAILED ATTR:".print_r($attr,true)
                                                      ." COND:".print_r($condition,true));
            return Ethna::raiseNotice("mem_master.last_login の UPDATE に失敗しました");
        }
    }

    /**
     * メルマガ希望職種リスト取得
     * メルマガID（mgz_user.mgz_usr_id）をキーに希望職種リスト（mgz_user_ocp.*）を取得する
     *
     * @param $mgzUsrId メルマガID
     * @return array 希望職種リスト
     */
    function getMgzUserOcpList( $mgzUsrId ) {

        $mgzUserOcpDao = new MgzUserOcpDao( $this->db, $this->backend );
        $retval = $mgzUserOcpDao->searchMailmagazineWishOcp(&$result,$mgzUsrId);

        if($retval == $this->config->config['_DB_ERROR']) {
            return Ethna::raiseError('システムエラーです。', E_CONSUMER_MGZ_GETONE);
        }

        $wishOcpList = array();
        foreach( $result as $key => $val ) {
            array_push($wishOcpList, $val['mgz_usr_ocp_id']);
        }

        return $wishOcpList;
    }

    /**
     * メルマガ希望エリアリスト取得
     *
     * @param $mgzUsrId メルマガID
     * @return array 希望エリアリスト "mgz_usr_pref_id"が添え字の配列
     */
    function getMgzUserWishAreaCityList( $mgzUsrId ) {

        $wishAreaCityList = array();

        $mgzUserPrefDao = new MgzUserPrefDao( $this->db, $this->backend );

        $retval = $mgzUserPrefDao->searchMailmagazineWishAreaPref(&$result,$mgzUsrId);
        if($retval == $this->config->config['_DB_ERROR']) {
            return Ethna::raiseError('システムエラーです。', E_CONSUMER_MGZ_GETONE);
        }

        $mgzUserCityDao = new MgzUserCityDao( $this->db, $this->backend );
        foreach( $result as $key => $val ) {
            $pref = $val['mgz_usr_pref_id'];
            $param = array( $mgzUsrId, $pref);
            $retval = $mgzUserCityDao->searchMailmagazineWishAreaCity(&$mgzUserCityResult,$param);
            if($retval == $this->config->config['_DB_ERROR']) {
                return Ethna::raiseError('システムエラーです。', E_CONSUMER_MGZ_GETONE);
            }
            $cities = array();
            foreach( $mgzUserCityResult as $key2 => $val2 ) {
                array_push($cities, $val2['mgz_usr_city']);
            }
            $wishAreaCityList["$pref"] = $cities;
        }

        return $wishAreaCityList;
    }

    /**
     * 希望職種文字列取得
     */
    public function getWishOcpString( $ocpIds, &$string ){
        $sysOccupationDao = new SysOccupationDao( $this->db, $this->backend );
        foreach( $ocpIds as $key => $val ) {
            if($sysOccupationDao->getSysOcpName($resultOcp,$val) !== $this->config->config['_DB_OK']){
                return Ethna::raiseError('システムエラーです。', E_CONSUMER_RESIGN);
            }
            $string .= ($key>0) ? "、" : "";
            $string .= $resultOcp;
        }
    }
    public function getWishOcpStringArray( $ocpIds, &$stringArray ){
        $sysOccupationDao = new SysOccupationDao( $this->db, $this->backend );
        $stringArray = array();
        foreach( $ocpIds as $key => $val ) {
            if($sysOccupationDao->getSysOcpName($resultOcp,$val) !== $this->config->config['_DB_OK']){
                return Ethna::raiseError('システムエラーです。', E_CONSUMER_RESIGN);
            }
            array_push($stringArray, $resultOcp);
        }
    }
    /**
     * 希望エリア文字列取得
     */
    public function getWishAreaCityString( $prefId, $cityIds, &$string ) {
        $sysPrefDao = new SysPrefectureDao( $this->db, $this->backend );
        if( $sysPrefDao->getPrefName($prefResult,$prefId) !== $this->config->config['_DB_OK'] ) {
            return Ethna::raiseError('システムエラーです。', E_CONSUMER_RESIGN);
        }

        $string .= $prefResult ."：";

        $sysAreCityDao = new SysAreCityDao( $this->db, $this->backend );
        foreach( $cityIds as $city_key => $city_val ) {
            $cityResult = "";
            if($city_val!=0){
                if( $sysAreCityDao->getSysAreCityName($cityResult,$city_val) !== $this->config->config['_DB_OK'] ) {
                    return Ethna::raiseError('システムエラーです。', E_CONSUMER_RESIGN);
                }
            } else {
                $cityResult = $prefResult . "全域";
            }
            $string .= ($city_key>0) ? "、" : "";
            $string .= $cityResult;
        }
    }
    public function getWishAreaCityStringArrayList( $wish_area_city_list, &$stringArrayList ) {

        $sysPrefDao = new SysPrefectureDao( $this->db, $this->backend );
        $sysAreCityDao = new SysAreCityDao( $this->db, $this->backend );

        $stringArrayList = array();
        foreach( $wish_area_city_list as $v ){
            $tmp = explode(",",$v);
            $prefId = array_shift($tmp);
            if( $sysPrefDao->getPrefName($prefResult,$prefId) !== $this->config->config['_DB_OK'] ) {
                return Ethna::raiseError('システムエラーです。', E_CONSUMER_RESIGN);
            }
            $wishCityList = array();
            foreach( $tmp as $cityId ){
                if($cityId!=0){
                    if( $sysAreCityDao->getSysAreCityName($cityResult,$cityId) !== $this->config->config['_DB_OK'] ) {
                        return Ethna::raiseError('システムエラーです。', E_CONSUMER_RESIGN);
                    }
                } else {
                    $cityResult = $prefResult . "全域";
                }
                array_push($wishCityList, $cityResult);
            }
            $stringArrayList[$prefResult] = $wishCityList;
        }
    }
    //#238 begin
    public function setUserAgentForMem($mem_id, $isUpdate=true) {
    	$this->logger->log(LOG_DEBUG, "Begin method:". __METHOD__. "()");
    	
    	// HTTP_USER_AGENT
    	$agent = Net_UserAgent_Mobile::factory();
    	
    	$UserAgent = '';
        if(!PEAR::isError($agent)){
            $UserAgent = $agent->getUserAgent();
        }
        $UserAgent = ($UserAgent) ? $UserAgent : 'unknown';
        
        //update user agent
        if ($isUpdate) {
        	
            $memMasterDao = new MemMasterDao( $this->db, $this->backend );
        	$attr = array(
			        	'mem_user_agent' => $UserAgent
			        	,'mem_user_agent_modified' => date('Y-m-d H:i:s')
			        	);
        	
        	$condition = array( array('mem_id','=', $mem_id ) ) ;
        	
        	if( $memMasterDao->update( $attr, $condition) != $this->config->config['_DB_OK'] ) {
        		$this->logger->log(LOG_ERR,__METHOD__."() UPDATE mem_master FAILED ATTR:".print_r($attr,true)
        																	." COND:".print_r($condition,true));
        		return Ethna::raiseError("DBエラーです", E_DB_ERROR);
        	}
        }
    	
    	$this->logger->log(LOG_DEBUG, "End method:". __METHOD__. "()");
    	return $UserAgent;
    }
    //#238 end
    
	//#242 begin by Luvina 
    /**
    * get city for search condition
    * @param array $aryCity
    * @param $prefId
    * @param $cityId
    */
    function getCityCondition(&$aryCity, &$aryPref, $prefId, $cityId){
    
    	$nearAreaMasterDao = new NearAreaMasterDao($this->db, $this->backend);
                        	
        $rs = $nearAreaMasterDao->getNearAreaUsrCity($aryCity, $prefId, $cityId);
        
        if( $rs == $this->config->config['_DB_ERROR'] ) {
        	$nearAreaMasterDao->rollback();
        	return Ethna::raiseNotice('DBエラーです。', E_CONSUMER_REGIST_ERROR);
        }
        
    	foreach($aryCity as $city) {	
	        $prefId = (int)substr($city, 0, 2);
	        $aryPref[$prefId] = $prefId;
        }
    }
    
    
    function getAdvList() {
	    if(!$this->af->get('city')){
        	$this->af->set('city',array());
        }
        if(!$this->af->get('city_all')){
        	$this->af->set('city_all',array());
        }
        
        // FIXME:都道府県リンクで遷移した場合、POST GET の2重送信できちゃうので POST をやめることを検討する必要あり！
        ini_set('memory_limit', '256M');
        
        // 検索条件をセット
        $formArray = $this->af->getArray();
        $this->af->setAppNE(_CONSUMER_MYSEARCH_SEARCH_FORM_ARRAY, $formArray);
        
//        $this->logger->log(LOG_DEBUG, '/// my search form array:'.print_r($formArray, true));

        // マネージャに渡す検索条件連想配列作成
        $cndArray = $this->_make_search_condition();
        
        //#{110} start maintance process: Luvina-company
        
        $flg = (!is_numeric($this->af->get('mms')) || (int)$this->af->get('mms') <= 0);
       
        //#329 begin modify
        /*
        $this->newSearch($cndArray, $flg);
        */
        /* @var $mgr Kj_PublicSearchManager */
        $mgr = $this->backend->getManager("PublicSearch");
        $mgr->getProjectSearchNew($cndArray, _CONSUMER_MYSEARCH_SEARCH_RESULT, _CONSUMER_MYSEARCH_SEARCH_PAGE, $flg, true, false);
        //#329 end modify
    }
    
    //#{110} start maintance process: Luvina-company
    /**
     *  Logic search new
     *
     * @return name view new
     */
     //#166 begin modify
     /*
    function newSearch($cndArray) {
    */
    //#329 begin commentout
    /*
    function newSearch($cndArray, $flg) {
    */
    //#166 end modify
         /* @var $mgr Kj_PublicSearchManager */
    /*
        $mgr = $this->backend->getManager("PublicSearch");
        // 検索条件にマッチする広告のID取得（旧search.phpのせい）
        $result = array();
        $aryPrior = array();
        $advDepId = array();
       
        $advDepIds = $mgr->countPublicSearch($cndArray);
      
        if(count($advDepIds) == 0) {
        	$city = array();
			$this->af->set('city',$city );
			$cndArray = $this->_make_search_condition();
        	$advDepIds = $mgr->countPublicSearch($cndArray);
        }
        */
        //process get $advDepIds priority 
        //#329 start modify by luvina
        /* $aryPrior = $mgr->get_prior_adv_ids($advDepIds); */  
        //#329 end modify by luvina
        /*
        //process paging
        $this->_update_page_info($page, $advDepIds);
        
        //get advDepId for each page
        $mgr->getAdvDepIdEachPage($advDepId, $page, $advDepIds);
        
         // 検索結果件数をセット
        $this->af->setAppNE('_search_result_count', $page['cnt']);
        
        $ot = $this->af->get('ot');
        
        if(strcmp($this->af->get('new_ads'), 'new') === 0){
            $ot = (empty($ot)) ? _ADV_NEW : $ot;
            //#183 start modify by Luvina
            $this->af->set('is_new_ads', 1);
            //#183 end modify by Luvina
        }
        
        $ots = $this->af->get('ots');// 給与順の場合の年俸・月給・時給ソート
       
        if(count($advDepId) > 0) {
         */
            //get result 
	    //#166 begin modify
	    /*
            $result = $mgr->search($advDepId, $advDepIds, $aryPrior, $ot, $ots);
	    */
        /*
	    $result = $mgr->search($advDepId, $advDepIds, $aryPrior, $ot, $ots, $flg, true);
	    //#166 end modify
        }
        */
        /*
        if($result == $this->config->config["_DB_ERROR"]) {
            $this->af->setAppNE(_CONSUMER_MYSEARCH_SEARCH_RESULT, array());
        } else if(Ethna::isError($result)) {
            $this->af->setAppNE(_CONSUMER_MYSEARCH_SEARCH_RESULT, array());
        } else if(count($result) < 1) {
            $this->af->setAppNE(_CONSUMER_MYSEARCH_SEARCH_RESULT, array());
        } else {
        	$this->af->setAppNE(_CONSUMER_MYSEARCH_SEARCH_RESULT, $result);
        }
        */
        //#329 start modify by luvina
        /*  $this->af->setAppNE('pai', $aryPrior);   */
        //#329 end modify by luvina
        /*
    }
    */
    //#329 end commentout
    //#{110} end maintance process: Luvina-company
    //#329 begin changed
    public function _make_search_condition()
    {
    //#329 end changed
        $formArray    = $this->af->getArray();
        $cndArray     = array();// manager に渡す検索条件作成
        $queryStr     = "";     // ページャ用クエリ文字列
        $modeQueryStr = "";     // 表示モード変更用クエリ文字列
        
        // 都道府県
        // #442 Begin modify
        $stationForms = $formArray["station"];
        $stations = array();
        if(is_array($stationForms) && count($stationForms)>0) {
        	foreach($stationForms as $stationForm) {
        		if(is_numeric($stationForm)) {
        			$stations[] = $stationForm;
        		}
        		$queryStr     .= "&station[]={$stationForm}";
        		$modeQueryStr .= "&station[]={$stationForm}";
        	}
        	$cndArray["station"] = $stations;
        } else {
	        $prefForms = $formArray["pref"];
	        $prefs = array();
	        if(is_array($prefForms)) {
	            foreach($prefForms as $prefForm) {
	                if(is_numeric($prefForm)) $prefs[] = $prefForm;
	                $queryStr     .= "&pref[]={$prefForm}";
	                $modeQueryStr .= "&pref[]={$prefForm}";
	            }
	        }
	        //#590 start maintenance process: Luvina company
	        //$cndArray["pref"] = $prefs;
	        //#590 end maintenance process: Luvina company 

	        // 市区町村
	        $cityForms = $formArray["city"];
	        $cities = array();
	        if(is_array($cityForms)) {
	            foreach($cityForms as $cityForm) {
	                if(is_numeric($cityForm)) {
	                    $cities[] = $cityForm;
	                    //#590 start maintenance process: Luvina company
	                    $indexPref = array_search(substr($cityForm, 0, 2), $prefs);
	                    if($indexPref !== false) {
	                    	unset($prefs[$indexPref]);
	                    }
	                    //#590 end maintenance process: Luvina company
	                    $cityChecked = true;
	                }
	                $queryStr     .= "&city[]={$cityForm}";
	                $modeQueryStr .= "&city[]={$cityForm}";
	            }
	        }
	        $cndArray["city"] = $cities;
	        //#590 start maintenance process: Luvina company
	        $cndArray["pref"] = array_values($prefs);
	        //#590 end maintenance process: Luvina company
	        
        }
        // #442 End modify
        // 職種
        $occpForms = $formArray["occupation"];
        $occs = array();
        if(is_array($occpForms)) {
            foreach($occpForms as $occpForm) {
                if(is_numeric($occpForm)) {
                    $occs[] = $occpForm;
                    $occpChecked = true;
                }
                $queryStr     .= "&occupation[]={$occpForm}";
                $modeQueryStr .= "&occupation[]={$occpForm}";
            }
        }
        $cndArray["occupation"] = $occs;
        
        // 雇用形態
        $frmForms = $formArray["form"];
        $frms = array();
        if(is_array($frmForms)) {
            foreach($frmForms as $frmForm) {
                if(is_numeric($frmForm)) {
                    $frms[] = $frmForm;
                    $frmChecked = true;
                }
                $queryStr     .= "&form[]={$frmForm}";
                $modeQueryStr .= "&form[]={$frmForm}";
            }
        }
        $cndArray["form"] = $frms;
        
        // ページャリンク・モード変更用クエリ文字列
        // [KJ_RENEWAL] POST になったため不要...（デザイナさん向け検索リンク用に使う
        $this->af->setAppNE(_CONSUMER_MYSEARCH_SEARCH_QUERY,      $queryStr);
        $this->af->setAppNE(_CONSUMER_MYSEARCH_SEARCH_MODE_QUERY, $modeQueryStr);
        $this->af->set('searchquery',$queryStr);
        
    	return $cndArray;
    }
    
/**
     * ページ情報を保持した連想配列の情報を更新する。
     *
     * @param array $page
     */
    //#329 begin commentout
    /*
    private function _update_page_info(&$page, $advIds = array(), $priorAdvIds = array())
    {
        if(!$this->session->isStart()) $this->session->start();
        
        // ページ情報
        $_pageNo = $this->af->get('page');
        $_s      = $this->af->get('s');
        $toPage = (is_numeric($_pageNo)) ? $_pageNo : (is_numeric($_s) ? $_s : 1);
        $page = $this->session->get(_CONSUMER_MYSEARCH_SEARCH_PAGE);
        $advNum   = (is_array($advIds)) ? count($advIds) : 0;
        $priorNum = (is_array($priorAdvIds)) ? count($priorAdvIds) : 0;
        /*@var $mgr Kj_PublicManager/
        $mgr = $this->backend->getManager("Public");
         //#{110} start maintance process: Luvina-company
        if($mgr->isSearchNew()) {
            $numPage = $mgr->getOutputRecord();
            $mgr->movePageTo($page, ($priorNum + $advNum), $toPage, $numPage);
        } else {
            $mgr->movePageTo($page, ($priorNum + $advNum), $toPage);
        }
        //#{110} end maintance process: Luvina-company
        $this->session->set(_CONSUMER_MYSEARCH_SEARCH_PAGE, $page);
        
        $this->af->set('page', $toPage);
    }
    */
    //#329 end commentout
    //#242 end by Luvina 
    
    //#302 begin by Luvina
    //#315
    //public function getHelloWorkDetail($adv_hw_introcom){
    public function getHelloWorkDetail(&$prefName, &$cityName, $adv_hw_introcom, $frmId, $prefId, $cityId){
    //#315
    	$condition = array();
    	$columns = array();
    	if(!$this->session->get('MEM_ID')) {
    		$columns = array(
	    		'adv_hw_pref_id', 
	    		'adv_hw_city_id', 
	    		'adv_hw_name',
				'adv_hw_top_position',
				'adv_hw_emp_work',
				'adv_hw_emp_type',
				'adv_hw_workplace',
				'adv_hw_url',
				'adv_hw_window'
	    		);
    	}
    	$now = date('Y-m-d');
		
    	//#302 Begin update request by Luvina
		$introcom = explode("-",$adv_hw_introcom);
		$condition[] = array('adv_hw_id_1', '=', (int)$introcom[0]);
		$condition[] = array('adv_hw_id_2', '=', (int)$introcom[1]);
    	//$condition[] = array('adv_hw_introcom', '=', $adv_hw_introcom);
    	//#302 End update request by Luvina
    	$condition[] = array('adv_hw_status', '=', 0);
    	$condition[] = array('date(adv_hw_date_started)', '<=', $now);
    	$condition[] = array('date(adv_hw_date_expired)', '>=', $now);
    	//#315 Begin
    	//#449 start comment out by Luvina
    	/*if($frmId) {
    		if($frmId == 1) {
    			$frm = "正社員";
    		} elseif($frmId == 2) {
    			$frm = "正社員以外";
    		}
    		if($frm) {
    			$condition[] = array('adv_hw_emp_type', '=', $frm);
    		}
    	}
    	if($prefId) {
    		$condition[] = array('adv_hw_pref_id', '=', $prefId);
    	}
    	if($cityId) {
    		$condition[] = array('adv_hw_city_id', '=', $cityId);
    	}*/
    	//#449 end comment out by Luvina
    	//#315 End
    	$dao = new AdvHelloWorkDao($this->db, $this->backend);
    	$ret = $dao->getList($result, $condition, false, false, $columns);
    	if( $ret == $this->config->config['_DB_ERROR'] ) {
        	return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
        }
        //#315 Begin modify
        if(count($result) > 0 || $prefId || $cityId) {
        	$prefId = ($prefId) ? $prefId : $result[0]['adv_hw_pref_id'];
        	$cityId = ($cityId) ? $cityId : $result[0]['adv_hw_city_id'];
        	if($prefId) {
	        	$prefDao = new SysPrefectureDao($this->db, $this->backend);
	        	$ret = $prefDao->getPrefName($prefName, $prefId);
		        if( $ret == $this->config->config['_DB_ERROR'] ) {
		        	return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
		        }
		        if(count($result) > 0) {
	        		$result[0]['sys_pref_namefull'] = $prefName;
		        }
        	}
        	if($cityId) {
	        	$cityDao = new SysAreCityDao($this->db, $this->backend);
	        	$ret = $cityDao->getSysAreCityName($cityName, $cityId);
		        if( $ret == $this->config->config['_DB_ERROR'] ) {
		        	return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
		        }
		        if(count($result) > 0) {
	        		$result[0]['sys_are_city_name'] = $cityName;
		        }
        	}
        }
        //#315 End modify
    	return $result[0];
    }
    //#302 end by Luvina
    
    //#311 begin
    public function getFirstProjectNotOther(&$aryResult) {
    	
    	$aryAdvDepId = $this->session->get('ADV_DEP_ID');
    	
    	if (count($aryAdvDepId) == 0) {
    		return false;
    	}
    	
    	$aryAdvId = $aryDepId = array();
    	foreach ($aryAdvDepId as $value) {
    		$aryTmp = explode(',', $value);
    		$aryAdvId[$aryTmp[0]] = (int)$aryTmp[0];
    		$aryDepId[$aryTmp[1]] = (int)$aryTmp[1];
    	}
    	$strOrderAdv = join(',', $aryAdvId);
    	$strOrderDep = join(',', $aryDepId);
    	
    	$strId = "(" . join("),(", $aryAdvDepId) . ")";
        $sql = "SELECT 
				    adv_master.adv_id
				    ,adv_department.cmp_dep_id
				    ,adv_master.adv_position
				    ,cmp_department.cmp_dep_city
				    ,cmp_department.cmp_dep_pref
				FROM adv_department
				INNER JOIN adv_master ON adv_master.adv_id = adv_department.adv_id
				INNER JOIN cmp_department ON cmp_department.cmp_dep_id = adv_department.cmp_dep_id
				WHERE 
				    cmp_department.cmp_dep_city NOT IN ('99999')
				    AND (adv_department.adv_id,adv_department.cmp_dep_id) IN ({$strId})
				ORDER BY FIELD(adv_department.adv_id,{$strOrderAdv}), FIELD(adv_department.cmp_dep_id,{$strOrderDep})
				LIMIT 1
                ";
        $params = array();
        $result = $this->db->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
        if (Ethna::isError($result)) {
            $this->logger->log(LOG_EMERG, print_r($result->getMessage(), true));
            return Ethna::raiseError("DBエラーです", E_DB_ERROR);
        }
        
        $aryResult = array();
        foreach ($result as $value) {
        	$aryResult['adv_id'] = $value['adv_id'];
        	$aryResult['cmp_dep_id'] = $value['cmp_dep_id'];
        	$aryResult['adv_position'] = $value['adv_position'];
        	$aryResult['cmp_dep_city'] = $value['cmp_dep_city'];
        	$aryResult['cmp_dep_pref'] = $value['cmp_dep_pref'];
        }
        
        return count($aryResult) > 0;
    }
    
    public function getResultSearchList() {
    	//#444 start modify By Luvina
    	/* $searchModeOff = $this->config->config['NEW_SEARCH_MODE_OFF'];
    	if ($searchModeOff) {
    		return ;
    	} */
    	//#444 end modify By Luvina
    	$aryProject = array();
    	$boolResult = $this->getFirstProjectNotOther($aryProject);
    	
    	if (!$boolResult) {
    		return ;
    	}
    	
    	$memId = $this->session->get('MEM_ID');
    	$advId = $aryProject['adv_id'];
    	$depId = $aryProject['cmp_dep_id'];
    	$cityId = $aryProject['cmp_dep_city'];
    	$prefId = $aryProject['cmp_dep_pref'];
    	
    	//Get form
    	$memApplyDao = new MemApplyDao($this->db, $this->backend, null);
        $memApplyCond = array(
	        array('mem_id', '=', $memId),
	        array('mem_app_adv_id', '=', $advId),
	        array('mem_app_dep_id', '=', $depId)
        );
        $retval = $memApplyDao->getList($memApply, $memApplyCond, false, true);
        if($retval == $this->config->config['_DB_ERROR']) {
            return Ethna::raiseError("DBエラーです", E_DB_ERROR);
        }
        $frmId  = $memApply->mem_app_frm_id;
        $this->af->set('form',array($frmId));
        
        //Get position
        $sysPos = array();
        $sysPosDao = new SysPositionDao($this->db, $this->backend);
        $sysPosCond = array(array('sys_pos_id', '=', $aryProject['adv_position']));
        $retval = $sysPosDao->getSysPosOccupationList($sysPos, $sysPosCond);
        if($retval == $this->config->config['_DB_ERROR']) {
            return Ethna::raiseError("DBエラーです", E_DB_ERROR);
        } else if($retval == $this->config->config['_DB_EMPTY']) {
           $this->logger->log(LOG_DEBUG, "広告の職種が見つかりません。");
        }
        
        $aryOccupation = array();
        foreach ($sysPos as $value) {
        	$aryOccupation[] = $value['sys_pos_occupation'];
        }
        $this->af->set('occupation',$aryOccupation );
        
        //get near city
        $aryCity = $aryPref = array();
        $this->getCityCondition($aryCity, $aryPref, $prefId, $cityId);
        $this->af->set('city',$aryCity );
        $this->af->set('pref',$aryPref);
        //#444 start modify by Luvina
        //comment out logic set pattent sort
        //Get pattern sort
        /* @var $mgrResult Kj_PublicManager */
        /* $mgrResult = $this->backend->getManager('Public');
        $intPattern = $mgrResult->getPatternSort();
        $this->config->set('NEW_SEARCH_MODE_SORT_PATTERN', $intPattern); */
        //#444 end modify by Luvina
        //execute search
        $this->af->set('re-search',1);
        $this->getAdvList();
        
    }
    //#311 end
    
    //#325 begin
    public function getListHelloWork(&$prefName, $prefId,$cityId, $offset=0, $limit=30, $count = false) {
    	$condition = $columns = array();
    	$strSort = '  ORDER BY adv_hw_date_started DESC ';
    	if ($count) {
    		$columns = array('count(*) AS cnt');
    	}
    	else {
    		
    		$columns = array(
	            'adv_hw_introcom',
	            'adv_hw_pref_id',
	            'adv_hw_city_id',
	            'adv_hw_frm_id',
	            'adv_hw_name',
	            'adv_hw_top_position',
	            'adv_hw_workplace',
	            'adv_hw_emp_type',
	            'adv_hw_emp_work',
	            'adv_hw_salary'
	        );
	        
	        
            $sort_flg = (int)$this->af->get('sort_flg');
            if ($sort_flg == 1) {
            	$strSort = ' ORDER BY CAST(adv_hw_salary_lower AS UNSIGNED INT) DESC ';
            }
            elseif ($sort_flg == 2) {
            	$strSort = ' ORDER BY CAST(adv_hw_salary_upper AS UNSIGNED INT) DESC ';
            }
            elseif ($sort_flg == 3) {
            	$strSort = ' ORDER BY adv_hw_date_started DESC ';
            }
    	}
        
		$strColumn = join(',', $columns);
		
		//#333 begin edit
		$whereCity ="";
		if($cityId) {
			$whereCity .= " AND adv_hw_city_id = ? ";
		}
		$strSQL = " SELECT
		          {$strColumn}
		      FROM
		          adv_hellowork
		      WHERE
		          adv_hw_status = 0
		          AND date(adv_hw_date_started) <= date(now())
		          AND date(adv_hw_date_expired) >= date(now())
		          AND adv_hw_pref_id = {$prefId}
		          {$whereCity}
                  
		      {$strSort}
		      LIMIT {$limit} OFFSET {$offset}
		";
        //#333 end edit
        $params = $aryResult = array();
        $params[] = $cityId;
        $result = $this->db->db->getAll($strSQL, $params, DB_FETCHMODE_ASSOC);
        if (Ethna::isError($result)) {
            $this->logger->log(LOG_EMERG, print_r($result->getMessage(), true));
            return Ethna::raiseError("DBエラーです", E_DB_ERROR);
        }
        else {
        
	        if (!$count) {
	        
		        foreach ($result as $value) {
	                $aryResult[] = array(
	                    'adv_hw_introcom' => $value['adv_hw_introcom'],
	                    'adv_hw_pref_id' => $value['adv_hw_pref_id'],
	                    'adv_hw_city_id' => $value['adv_hw_city_id'],
	                    'adv_hw_frm_id' => $value['adv_hw_frm_id'],
	                    'adv_hw_name' => $value['adv_hw_name'],
	                    'adv_hw_top_position' => $value['adv_hw_top_position'],
	                    'adv_hw_workplace' => $value['adv_hw_workplace'],
	                    'adv_hw_emp_type' => $value['adv_hw_emp_type'],
	                    'adv_hw_emp_work' => $value['adv_hw_emp_work'],
	                    'adv_hw_salary' => $value['adv_hw_salary'],
	                );
	            }
	            
	            //Get pref name
	            $prefDao = new SysPrefectureDao($this->db, $this->backend);
	            $ret = $prefDao->getPrefName($prefName, $prefId);
	            if( $ret == $this->config->config['_DB_ERROR'] ) {
	                return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
	            }
	        }
	        
            return ($count) ? $result[0]['cnt'] : $aryResult;
        }
    }
    
    public function checkPrefExist($prefid) {
		$prefDao = new SysPrefectureDao($this->db, $this->backend);
		$aryCond = array(array('sys_pref_id', '=', $prefid));
		$ret = $prefDao->normalCount($aryCond);
		if( $ret == $this->config->config['_DB_ERROR'] ) {
		    return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
		}
		
		return $ret > 0;
    }
    //#325 end

    // #478 Start maintenance process: Luvina company
    public function isPublishedAdv($adv_id, $cmp_dep_id, $option = 3) {
        $condition1 = $this->checkAdvStatus($adv_id, $cmp_dep_id, $option);
        if(!$condition1) {
            return false;
        }
        $condition2 = $this->isExistDep($cmp_dep_id);
        if(!$condition2) {
            return false;
        }
        return true;
    }
    public function checkAdvStatus($advId, $cmp_dep_id, $option) {
        $dao = new AdvMasterDao( $this->db, $this->backend );
        $result = $dao->countAdvById($advId, $cmp_dep_id, $option);
        if( $result === $this->config->config['_DB_ERROR'] ) {
        	return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
        }
        if(is_numeric($result) && $result > 0) {
            return true;
        }
        return false;
    }
    public function isExistDep($cmp_dep_id) {
        $cmpDepartmentDao = new CmpDepartmentDao( $this->db, $this->backend );
        $condition = array();
        $condition[] = array('cmp_dep_status','=',0);
        //#478 start maintance process: Luvina-company:【Bug ID: 1221】 comment:5 
        $condition[] = array('cmp_dep_temp_flg','=',0);
        //#478 end maintance process: Luvina-company:【Bug ID: 1221】 comment:5
        $condition[] = array('cmp_dep_id','=',$cmp_dep_id);
        $result = $cmpDepartmentDao->normalCount($condition);
        if( $result === $this->config->config['_DB_ERROR'] ) {
        	return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
        }
        if(is_numeric($result) && $result > 0) {
            return true;
        }
        return false;
    }
    // #478 End maintenance process: Luvina company
    //#594 start maintance process: Luvina-company 
    //Lay ve data cua hoi vien da duoc update tu sau ngay gio chi dinh
    public function getListMember(&$result, $aryCondition, $aryColumn=array(), $offset=false, $limit=false) {
        
        if (!is_array($aryColumn) || count($aryColumn) == 0) {
            $aryColumn = array(
                        'mem_id',
                        'mem_name',
                        'mem_kana',
                        'mem_sex',
                        'mem_birthday',
                        'mem_pref_id',
                        'mem_city_id',
                        'mem_address',
                        'mem_tel',
                        'mem_mobile',
                        'mem_mail',
                        'mem_frm_id',
                        'mem_career_id',
                        'mem_pr',
                        'site_status',
                        'created',
                        'mem_conversion',
                        'pre_created',
                        'pre_site_status',
                        'mem_pre_conversion',
                        'mem_pre_lp',
                        'delete_flg'
            );
        }
        
        $strOption = false;
        if ($offset !== false && $limit !== false) {
            $strOption = " LIMIT {$limit} OFFSET {$offset} ";
        }

        if (!is_object($this->memMasterDao)) {
            $this->memMasterDao = new MemMasterDao($this->db, $this->backend);
        }

        $ret = $this->memMasterDao->getList($result, $aryCondition, $strOption, false, $aryColumn);
        if ($ret === $this->config->config['_DB_OK']) {
            $aryTem = array();
            foreach ($result as $value) {
                $row = array();
                foreach ($aryColumn as $column) {
                    $row[$column] = $value[$column];
                }
                
                $aryTem[] = $row;
            }
            $result = $aryTem;
        }
        
        return $ret;
    }
    

    public function getCountMem($aryCondition) {

        if (!is_object($this->memMasterDao)) {
            $this->memMasterDao = new MemMasterDao($this->db, $this->backend);
        }

        $intCount = $this->memMasterDao->normalCount($aryCondition);
        return $intCount;
    }

    public function getMemSkillbyAryMemId($aryMemId) {
        $aryData = array();
        if(is_array($aryMemId) && count($aryMemId) > 0) {
            
            $condition = array(
                    array('mem_id', 'IN', $aryMemId)
            );
            $result = array();

            if (!is_object($this->memSkillDao)) {
                $this->memSkillDao = new MemSkillDao($this->db, $this->backend);
            }

            $ret = $this->memSkillDao->getList($result, $condition);
            if ($ret === $this->config->config['_DB_ERROR']) {
                return $this->config->config['_DB_ERROR'];
            } else if ($ret === $this->config->config['_DB_OK']) {
                foreach ($result as $value) {
                    $aryData[$value['mem_id']][] = $value['mem_skl_id'];
    			}
    		}
    	}
    	return $aryData; 
    }
    //#594 end maintance process: Luvina-company
    
    // #601 start maintenance process: luvina company
    /**
     * getMgzUserInfoForSimilarAdv
     * 
     * @param array $result
     * @param int $mgzUserId
     */
    public function getMgzUserInfoForSimilarAdv(&$result, $mgzUserId) {
        $mgzUserDao = new MgzUserDao( $this->db, $this->backend );
        $mgzUserOcpDao = new MgzUserOcpDao( $this->db, $this->backend );
        $mgzUserCityDao = new MgzUserCityDao( $this->db, $this->backend );
        
        $mgzUser = array();
        $mgzUserOcp = array();
        $mgzUserCity = array();
        
        $result['mem_frm_id'] = array();
        $result['mgz_usr_ocp_id'] = array();
        $result['mgz_usr_city'] = array();
        $result['mgz_usr_pref'] = array();
        
        // Get mgz_usr_mail by mgz_usr_id
        $cndMgzUser = array(array('mgz_usr_id', '=', $mgzUserId));
        $columnsMgzUser = array('mgz_usr_frm_id');
        $ret = $mgzUserDao->getList($mgzUser, $cndMgzUser, false, true, $columnsMgzUser);
        if($ret == $this->config->config['_DB_ERROR']) {
            return $this->config->config['_DB_ERROR'];
        }
        
        $mgzUser = (array)$mgzUser;
        if(is_array($mgzUser) && count($mgzUser) > 0) {
            $result['mem_frm_id'] = Kj_Util::trimAndExplode($mgzUser['mgz_usr_frm_id']);
        }
        
        // Get mgz_usr_ocp_id by mgz_usr_id
        $cndMgzOcp = array(array('mgz_usr_id', '=', $mgzUserId));
        $columnsMgzOcp = array('mgz_usr_ocp_id');
        $ret = $mgzUserOcpDao->getList($mgzUserOcp, $cndMgzOcp, false, false, $columnsMgzOcp);
        if($ret == $this->config->config['_DB_ERROR']) {
            return $this->config->config['_DB_ERROR'];
        }
        
        if(is_array($mgzUserOcp) && count($mgzUserOcp) > 0) {
            foreach ($mgzUserOcp AS $val) {
                $result['mgz_usr_ocp_id'][] = $val['mgz_usr_ocp_id'];
            }
        }
        
        // Get mgz_usr_city by mgz_usr_id
        $cndCity = array(array('mgz_usr_id', '=', $mgzUserId));
        $columnsCity = array('mgz_usr_city', 'mgz_usr_pref_id');
        $ret = $mgzUserCityDao->getList($mgzUserCity, $cndCity, false, false, $columnsCity);
        if($ret == $this->config->config['_DB_ERROR']) {
            return $this->config->config['_DB_ERROR'];
        }
        
        if(is_array($mgzUserCity) && count($mgzUserCity) > 0) {
            foreach ($mgzUserCity AS $val) {
                if(isset($val['mgz_usr_city']) && $val['mgz_usr_city'] > 0) {
                    $result['mgz_usr_city'][] = $val['mgz_usr_city'];
                } else {
                    $result['mgz_usr_pref'][] = $val['mgz_usr_pref_id'];
                }
            }
        }
        
        return $this->config->config['_DB_OK'];
    }
    
    /**
     * makeSimilarAdvsForMagazine
     * 
     * @param string $memAddr
     * @param array $aryOcp
     * @param array $aryForm
     * @param array $aryPref
     * @param array $aryCity
     * @param int $mgzUsrId
     */
    public function makeSimilarAdvsForMagazine($memAddr, $aryOcp, $aryForm, $aryPref, $aryCity, $mgzUsrId = false) {
        $advMagazine = array();
        
        if($mgzUsrId) {
            $mgzUser = array();
            $ret = $this->getMgzUserInfoForSimilarAdv($mgzUser, $mgzUsrId);
            if($ret == $this->config->config['_DB_ERROR']) {
                return array();
            }
            
            $aryOcp = $mgzUser['mgz_usr_ocp_id'];
            $aryForm = $mgzUser['mem_frm_id'];
            $aryPref = $mgzUser['mgz_usr_pref'];
            $aryCity = $mgzUser['mgz_usr_city'];
        }
        
        $advMasterDao = new AdvMasterDao($this->db, $this->backend);
        $ret = $advMasterDao->getSimilarAdvsForMagazine($advMagazine, $aryOcp, $aryForm, $aryPref, $aryCity);
        if($ret == $this->config->config['_DB_ERROR']) {
            return array();
        }
        
        /* @var $mgzMgr Kj_MgrMgzManager */
        $mgzMgr = $this->backend->getManager('MgrMgz');
        if(is_array($advMagazine) && count($advMagazine) > 0) {
            foreach ($advMagazine as $key => $info) {
                $advMagazine[$key]['adv_emp_workplace'] = preg_replace(array('/[\r\n]+/', '/[\r]+/','/[\n]+/'), array(' ',' ',' '), $info['sys_pref_namefull'].$info['sys_are_city_name']);
                //Salary
                $advSalary = $mgzMgr->_makeAdvSalaryStr((object)$info);
                $depSalary = $mgzMgr->_makeDepSalaryStr((object)$info);
                $salary = ($depSalary) ? $depSalary : $advSalary;
                
                $salary = preg_replace(array('/[\r\n]+/', '/[\r]+/','/[\n]+/'), array(' ',' ',' '), $salary);
                $salary = Kj_Util::_trimFullSize($salary); 
                $advMagazine[$key]['salary'] = $salary;
                
                //get 雇用形態 
                $strType = ($info['cmp_dep_type']) ? $info['cmp_dep_type'] : $info['adv_emp_type'];
                $strType = preg_replace(array('/[\r\n]+/', '/[\r]+/','/[\n]+/'), array(' ',' ',' '), $strType);
                $strType = Kj_Util::_trimFullSize($strType); 
                $advMagazine[$key]['form_type'] = Kj_Util::maxByte($strType, 40);
                
                //adv_top_copy
                $info['adv_top_copy'] = preg_replace(array('/[\r\n]+/', '/[\r]+/','/[\n]+/'), array(' ',' ',' '), $info['adv_top_copy']);
                $info['adv_top_copy'] = Kj_Util::_trimFullSize($info['adv_top_copy']); 
                $advMagazine[$key]['adv_top_copy'] = Kj_Util::maxByte($info['adv_top_copy'], 40);
                
                //Check mail PC OR MB
                //ADD 2013/03/26 SMS Warning対応
                if($mgzMgr->_isMobileAddress($memAddr,$_SERVER['HTTP_USER_AGENT'])) {
                    $advMagazine[$key]['adv_top_position'] = Kj_Util::maxByte($simAdvs[$key]['adv_top_position'], 40);
                    $advMagazine[$key]['cmp_dep_name'] = Kj_Util::maxByte($simAdvs[$key]['cmp_dep_name'], 40);
                }
            }
        }
        
        return $advMagazine;
    }
    // #601 end maintenance process: luvina company
    //#636 start maintance process: Luvina-company 
    /**
     * 
     * Execute search after login
     * @param Array $aryPrior: Array ID projects priority
     * @param Array $aryMPrior:Array ID projects priority hightest
     * @param Int $total: Total records
     * @param Int $limit: Number records on each page
     * @param Array $cndFromInput: array condition get from Form
     * @param Int $ot: Order type
     * @param Int $ots: Order type salary
     * @param Int $cPage: Page No current
     * @param Bool $isPrio = true: get project priority, = false: no get priority
     * 
     * @return Array projects for each page
     */
    public function getProjectSearchLogined(
                        &$aryPrior, 
                        &$aryMPrior, 
                        &$total, 
                        $limit, 
                        $cndFromInput = array(), 
                        $ot = false, 
                        $ots = false, 
                        $cPage = 1, 
                        $isPrioProcess = false,
                        $isPrioRandomFlg = false,
                        //#679 Start maintance process: Luvina-company:
                        $isNoGetMemInfoDB = false,
                        $aryUserInfo = array(),
                        $havePatternOther = true
                        //#679 end maintance process: Luvina-company:
                        //#787 start maintance process: Luvina-company : #comment:22 
                        , $aryAdvDepIdRemove = array()
                        //#787 end maintance process: Luvina-company : #comment:22 
                        //#841 start maintance process: Luvina-company 
                        , $usingSortPatternAlternate = false
                        //#841 end maintance process: Luvina-company
    ) {
        //#668 start maintance process: Luvina-company #comment:7
        $aryPattern = array(
            _PUBLIC_SEARCH_LOGINED_PATTERN_1,
            _PUBLIC_SEARCH_LOGINED_PATTERN_2,
            _PUBLIC_SEARCH_LOGINED_PATTERN_3,
            _PUBLIC_SEARCH_LOGINED_PATTERN_4,
            _PUBLIC_SEARCH_LOGINED_PATTERN_5,
            _PUBLIC_SEARCH_LOGINED_PATTERN_6,
            //#679 Start maintance process: Luvina-company:
            //_PUBLIC_SEARCH_LOGINED_PATTERN_OTHER,
        );
        
        if ($havePatternOther) {
        	$aryPattern[] = _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER;
        }
        
        foreach ($aryPattern as $pattern) {
            $cndUserPattern[$pattern] = $this->getConditionPattern($pattern, null, null,false,
                                                             $isNoGetMemInfoDB, $aryUserInfo);
	//#679 End maintance process: Luvina-company:
            //#668 start maintance process: Luvina-company 
            if ($cndUserPattern[$pattern] === $this->config->config['_DB_ERROR']) {
                return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
            }
            //#668 end maintance process: Luvina-company
            //#884 start maintance process: Luvina-company
            if($pattern !== _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER) {
                //insert condtion adv_nominal_price.adv_nml_price <> 1
                $cndUserPattern[$pattern][_PUBLIC_SEARCH_CONDITION_NOT_ADV_PRICE] = 1;
            }
            //#884 end maintance process: Luvina-company
        }
        //#787 start maintance process: Luvina-company : #comment:22
        $aryAdvDepIdExclude = array();
        if (is_array($aryAdvDepIdRemove) && count($aryAdvDepIdRemove) > 0) {
            $aryAdvDepIdExclude = array_merge($aryAdvDepIdExclude, $aryAdvDepIdRemove);
        }
        //#787 end maintance process: Luvina-company : #comment:22
        $adv_dep_ids = $this->getAdvDepIdsSearchLogined(
                                $aryPrior, 
                                $aryMPrior, 
                                $total, 
                                $limit, 
                                $cndFromInput, 
                                $cPage, 
                                $isPrioProcess,
                                $isPrioRandomFlg,
                                //#787 start maintance process: Luvina-company : #comment:22
                                //array(),
                                $aryAdvDepIdExclude,
                                //#787 end maintance process: Luvina-company : #comment:22
                                $cndUserPattern
                                //#841 start maintance process: Luvina-company
                                ,null,
                                $usingSortPatternAlternate
                                //#841 end maintance process: Luvina-company
                        );
        //#668 start maintance process: Luvina-company 
        if ($adv_dep_ids === $this->config->config['_DB_ERROR']) {
            return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
        }
        //#668 end maintance process: Luvina-company
        //#668 end maintance process: Luvina-company #comment:7
        if (!is_array($adv_dep_ids) || count($adv_dep_ids) ==0) {
            return array();
        }
        
        /* @var $mgrPublicSearch Kj_PublicSearchManager */
        $mgrPublicSearch = $this->backend->getManager("PublicSearch");
        //#636 start maintance process: Luvina-company : 【Bug ID: 1744】 #comment:36 
        $result = $this->search(
                            $adv_dep_ids, 
                            array(), 
                            $aryPrior, 
                            $ot, 
                            $ots, 
                            true, 
                            true, 
                            $aryMPrior
                  );
        //#636 end maintance process: Luvina-company : 【Bug ID: 1744】 #comment:36
        //Reformate array Prio
        $aryPriorTem = $aryMPriorTem = array();
        if (count($aryMPrior) > 0) {
            foreach ($aryMPrior as $mPrio) {
                $aryMPriorTem[] = $mPrio['adv_dep_id'];
            }
        }
        $aryMPrior = $aryMPriorTem;
	
        if (count($aryPrior) > 0) {
            foreach ($aryPrior as $prio) {
                $aryPriorTem[] = $prio['adv_dep_id'];
            }
        }
        $aryPrior = $aryPriorTem;
        
      return $result;
    }
    
     /**
     * 
     * Get ID project 
     * @param Array $aryPrior: Array ID projects priority
     * @param Array $aryMPrior:Array ID projects priority hightest
     * @param Int $total: Total records
     * @param Int $limit: Number records on each page
     * @param Array $cndFromInput: array condition get from Form
     * @param Int $cPage: Page No current
     * @param Bool $isPrio = true: get project priority, = false: no get priority
     * 
     * @return Array project id(adv_dep_id) for each page
     */
    //#668 start maintance process: Luvina-company 
    //#668 start maintance process: Luvina-company #comment:7
    //#766 start maintance process: Luvina-company
    //#841 start maintance process: Luvina-company
    public function getAdvDepIdsSearchLogined(
                        &$aryPrior, 
                        &$aryMPrior, 
                        &$total, 
                        $limit,
                        $cndFromInput = array(), 
                        $cPage = 1, 
                        $isPrioProcessFlg = false,
                        $isPrioRandomFlg = false,
                        $aryAdvDepIdExclude = array(),
                        $aryConditionPattern = array(),
                        $memId = null,
                        $usingSortPatternAlternate = false
    ) {
        //#841 end maintance process: Luvina-company
        //#766 end maintance process: Luvina-company
        //#668 end maintance process: Luvina-company #comment:7
        //#668 end maintance process: Luvina-company
        $aryAdvDepIds = array();
        
        //step 8
        $numDate = 45;//Apply in the last 45 days
        //#766 start maintance process: Luvina-company
        $aryAdvDepIdHistory = $this->getHistoryApply($cndFromInput, $numDate, $memId);
        //#766 end maintance process: Luvina-company
        //#668 start maintance process: Luvina-company
        //#668 start maintance process: Luvina-company #comment:7
        if ($aryAdvDepIdHistory === $this->config->config['_DB_ERROR']) {
            return $this->config->config['_DB_ERROR'];
            //return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
        } elseif(count($aryAdvDepIdExclude) > 0) {
            $aryAdvDepIdHistory = array_merge($aryAdvDepIdHistory, $aryAdvDepIdExclude);
        }
         //#668 end maintance process: Luvina-company #comment:7
	 unset($aryAdvDepIdExclude);
         $aryAdvDepIdHistory = array_unique($aryAdvDepIdHistory);
        //#668 end maintance process: Luvina-company 
        // step 9
        $total = $this->getCountSearchLogined($cndFromInput, $aryAdvDepIdHistory);
        //#668 start maintance process: Luvina-company #comment:7
        if ($total === $this->config->config['_DB_ERROR']) {
            return $this->config->config['_DB_ERROR'];
            //return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
        }
        //#668 end maintance process: Luvina-company #comment:7
        if($total == 0) {
            return $aryAdvDepIds;
        }
        
        $cPage = ($cPage < 1) ? 1 : $cPage;
        $endPage = ceil(($total/$limit));
        $cPage = ($cPage > $endPage) ? $endPage : $cPage;
        
        $offset = ($cPage - 1) * $limit;
        //#841 start maintance process: Luvina-company
        $limitMPrio = $limitPrio = 0;
        if($usingSortPatternAlternate === true) {
            $aryConfig = $this->config->get('configPattern');
            $countPatternPrio = 0;
            $maxPattern = 0;
            if(isset($aryConfig['Prio'])) {
                $limitMPrio = $aryConfig['Prio'][0]; 
                $limitPrio = $aryConfig['Prio'][1]; 
                //set data back $aryConfig['Prio'] = $limitMPrio + $limitPrio
                $aryConfig['Prio'] = $limitMPrio + $limitPrio;
            }
            $aryConfigTmp = $aryConfig;
            unset($aryConfigTmp['General']);
            $maxPattern = max($aryConfigTmp);
        }
        //#841 end maintance process: Luvina-company
        // step 10 start
        if($isPrioProcessFlg) {
            //#700 start maintance process: Luvina-company 
            $isGetPrio =    !$isPrioRandomFlg && 
                            ((is_array($aryPrior) && count($aryPrior) > 0)
                            || (is_array($aryMPrior) && count($aryMPrior) > 0));
            //#700 end maintance process: Luvina-company
            $advDepIdsPrio = array();
            if($isGetPrio) {
                $advDepIdsPrio = $this->getProjectPrio($cndFromInput, $aryAdvDepIdHistory);
                //#668 start maintance process: Luvina-company
                 if($advDepIdsPrio === $this->config->config['_DB_ERROR']) {
                     return $this->config->config['_DB_ERROR'];
                 }
                //#668 end maintance process: Luvina-company
            }
            //#700 start maintance process: Luvina-company
            /*
            if($isPrioRandomFlg) {
                $aryPrior = $this->getPriorIds($advDepIdsPrio);
            } else if(is_array($aryPrior) && count($aryPrior) > 0) {
                $aryPrior = $this->checkExistProjectPrio($advDepIdsPrio, $aryPrior);
            }
            
            if($isPrioRandomFlg) {
                //lay ra 2 qc uu tien nhat
                $aryMPrior = $this->getPriorIds($advDepIdsPrio, 2, 2);
            } else if(is_array($aryMPrior) && count($aryMPrior) > 0) {
                $aryMPrior = $this->checkExistProjectPrio($advDepIdsPrio, $aryMPrior);
            }
            */
            if(!$isPrioRandomFlg && is_array($aryPrior) && count($aryPrior) > 0) {
                $aryPrior = $this->checkExistProjectPrio($advDepIdsPrio, $aryPrior);
            }
            if(!$isPrioRandomFlg && is_array($aryMPrior) && count($aryMPrior) > 0) {
                $aryMPrior = $this->checkExistProjectPrio($advDepIdsPrio, $aryMPrior);
            }
            unset($advDepIdsPrio);
            
            if($isPrioRandomFlg) {
                //#841 start maintance process: Luvina-company
                if($usingSortPatternAlternate) {
                    $this->getProjectPriorByPattern5_6($aryMPrior, $aryPrior, $cndFromInput, $aryConditionPattern, $aryAdvDepIdHistory, $limitMPrio, $limitPrio);
                } else {
                    $this->getProjectPriorByPattern5_6($aryMPrior, $aryPrior, $cndFromInput, $aryConditionPattern, $aryAdvDepIdHistory);
                }
                //#841 end maintance process: Luvina-company
            }
            //#700 end maintance process: Luvina-company
        }
        if(!is_array($aryMPrior)) $aryMPrior = array();
        if(!is_array($aryPrior)) $aryPrior = array();
        
        if (count($aryMPrior) > 0) {
            foreach ($aryMPrior as $mPrio) {
                $aryAdvDepIds[] = $mPrio['adv_dep_id'];
            }
        }
        if (count($aryPrior) > 0) {
            foreach ($aryPrior as $prio) {
                $aryAdvDepIds[] = $prio['adv_dep_id'];
            }
        }
        // step 10 end 
        //#668 start maintance process: Luvina-company #comment:7
        /*
        $aryPattern = array(
            _PUBLIC_SEARCH_LOGINED_PATTERN_1,
            _PUBLIC_SEARCH_LOGINED_PATTERN_2,
            _PUBLIC_SEARCH_LOGINED_PATTERN_3,
            _PUBLIC_SEARCH_LOGINED_PATTERN_4,
            _PUBLIC_SEARCH_LOGINED_PATTERN_5,
            _PUBLIC_SEARCH_LOGINED_PATTERN_6,
            _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER,
        );
        */
        //foreach ($aryPattern as $pattern) {
        foreach ($aryConditionPattern as $pattern => $cndUserPattern) {
            //$cndUserPattern = $this->getConditionPattern($pattern);
            //#668 end maintance process: Luvina-company #comment:7
            if($this->checkConditionPattern($pattern, $cndFromInput, $cndUserPattern)) {
                $limitOfPattern = ($offset + $limit) - count($aryAdvDepIds);
                //#841 start maintance process: Luvina-company 
                if($usingSortPatternAlternate) {
                    $limitOfPattern = ($offset + $limit);
                    $maxGetGeneral = $maxPattern + count($aryPrior) + count($aryMPrior);
                    if($limitOfPattern < $maxGetGeneral) {
                        $limitOfPattern = $maxGetGeneral;
                    }
                }
                //#841 end maintance process: Luvina-company 
                $aryAdvDepIdOfPattern = $this->getProjectByPatternSearch(
                                                    $cndFromInput, 
                                                    $cndUserPattern, 
                                                    $aryAdvDepIdHistory, 
                                                    $aryAdvDepIds, 
                                                    $pattern,
                                                    $limitOfPattern
                                        );
                //#668 start maintance process: Luvina-company #comment:7
                if ($aryAdvDepIdOfPattern === $this->config->config['_DB_ERROR']) {
                    return $this->config->config['_DB_ERROR'];
                    //return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
                }
                //#668 end maintance process: Luvina-company #comment:7
                if(is_array($aryAdvDepIdOfPattern) && count($aryAdvDepIdOfPattern)) {
                    $aryAdvDepIds = array_merge($aryAdvDepIds, $aryAdvDepIdOfPattern);
                }
                //#841 start maintance process: Luvina-company
                if($usingSortPatternAlternate) {
                    if(count($aryAdvDepIds) >= $limitOfPattern) {
	                    break;
	                }
                } else {
	                if(count($aryAdvDepIds) >= ($offset + $limit)) {
	                    break;
	                }
                }
                //#841 end maintance process: Luvina-company 
            }
        }
        //#841 start maintance process: Luvina-company
        if($usingSortPatternAlternate === true) {
            //process remove priority 
            
            $countPrio = count($aryMPrior) + count($aryPrior);
            $aryAdvDepIds = array_slice($aryAdvDepIds, $countPrio);
            
            //sort project
            $aryConfig = $this->config->get('configPattern');
            $aryData = array();
            $aryMPriorTmp = $aryPriorTmp = array();
            foreach ($aryMPrior as $value) {
                $aryMPriorTmp[] = $value['adv_dep_id'];
            }
            
            foreach ($aryPrior as $value) {
                $aryPriorTmp[] = $value['adv_dep_id'];
            }
            $aryData['Prio'] = array_merge($aryMPriorTmp, $aryPriorTmp);
            $aryData['General'] = &$aryAdvDepIds;
            
            $aryProjectSort = Kj_Util::sortListPatternAlternate($aryConfig, $aryData);
            $aryAdvDepIds = array_merge($aryProjectSort, $aryAdvDepIds);
        }
        //#841 end maintance process: Luvina-company 
        $aryAdvDepIdOutput = array();
        if(is_array($aryAdvDepIds) && count($aryAdvDepIds)) {
            $aryAdvDepIdOutput = array_slice($aryAdvDepIds, $offset, $limit, true);
        }
        return $aryAdvDepIdOutput;
    }
    //#700 start maintance process: Luvina-company 
    //#727 start maintance process: Luvina-company
    //#841 start maintance process: Luvina-company
    private function getProjectPriorByPattern5_6(&$aryMPrior, &$aryPrior, $cndFromInput, $aryConditionPattern, $aryAdvDepIdHistory, $mPrioLimit = 2, $prioLimit = 5) {
        //#727 end maintance process: Luvina-company
        //$mPrioLimit = 2;
        //$prioLimit = 5;
        //#841 end maintance process: Luvina-company
        $aryPattern = array();
        $aryPattern[_PUBLIC_SEARCH_LOGINED_PATTERN_5] = $aryConditionPattern[_PUBLIC_SEARCH_LOGINED_PATTERN_5];
        //#727 start maintance process: Luvina-company
        /*
        $aryPattern[_PUBLIC_SEARCH_LOGINED_PATTERN_6] = $aryConditionPattern[_PUBLIC_SEARCH_LOGINED_PATTERN_6];
        */
        //#727 end maintance process: Luvina-company
        $aryCmpIdNotInPrio = $aryCmpIdNotInMPrio = array();
        foreach ($aryPattern as $pattern => $cndUserPattern) {
            if($this->checkConditionPattern($pattern, $cndFromInput, $cndUserPattern)) {
                $advDepIdsPrio = $this->getProjectPrio($cndFromInput, $aryAdvDepIdHistory, false, $cndUserPattern);
                if($advDepIdsPrio === $this->config->config['_DB_ERROR']) {
                    return $this->config->config['_DB_ERROR'];
                }
                if(count($aryMPrior) < $mPrioLimit) {
                    $aryMPrior1 = $this->getPriorIds($advDepIdsPrio, 2, $mPrioLimit - count($aryMPrior), $aryCmpIdNotInMPrio);
                    if(is_array($aryMPrior1) && count($aryMPrior1) > 0) {
                        $aryMPrior = array_merge($aryMPrior, $aryMPrior1);
                    }
                }
                
                if(count($aryPrior) < $prioLimit) {
                    $aryPrior1 = $this->getPriorIds($advDepIdsPrio, 1, $prioLimit - count($aryPrior), $aryCmpIdNotInPrio);
                    if(is_array($aryPrior1) && count($aryPrior1) > 0) {
                        $aryPrior = array_merge($aryPrior, $aryPrior1);
                    }
                }
                //check Unique ary prior
                $this->uniqueAryPrior($aryPrior);
                $this->uniqueAryPrior($aryMPrior);
                if(is_array($aryMPrior) && count($aryMPrior) > 0) {
                    foreach ($aryMPrior as $value) {
                        $aryCmpIdNotInMPrio[$value['cmp_id']] = $value['cmp_id']; 
                    }
                }
                if(is_array($aryPrior) && count($aryPrior) > 0) {
                    foreach ($aryPrior as $value) {
                        $aryCmpIdNotInPrio[$value['cmp_id']] = $value['cmp_id']; 
                    }
                }
                if(count($aryPrior) == $prioLimit && count($aryMPrior) == $mPrioLimit) {
                    break;
                }
                
                //#727 start maintance process: Luvina-company
                /*
                if(is_array($advDepIdsPrio) && count($advDepIdsPrio) > 0) {
                    $advDepIds = array();
                    foreach ($advDepIdsPrio as $value) {
                        $advDepIds[$value['adv_dep_id']] = $value['adv_dep_id'];
                    }
                    $aryAdvDepIdHistory = array_merge($aryAdvDepIdHistory, $advDepIds);
                }
                */
                //#727 end maintance process: Luvina-company
            }
        }
        unset($advDepIdsPrio);
    }
    
    private function uniqueAryPrior(&$aryPrior) {
        $aryCheckPrior = array();
        foreach ($aryPrior as $key => $value) {
            if(!isset($aryCheckPrior[$value['adv_dep_id']])) {
                $aryCheckPrior[$value['adv_dep_id']] = $value['adv_dep_id'];
            } else {
                unset($aryPrior[$key]);
            }
        }
    }
    //#700 end maintance process: Luvina-company 
    /**
     * 
     * Check exist adv_dep_id priority
     * @param Array $aryAdvDepId: array all priority
     * @param Array $aryPai: array priority get from $form
     * 
     * @return Array projects priority exist
     */
    private function checkExistProjectPrio($aryAdvDepId, $aryPai) {
        
    	$aryPrior = array();
    	
        foreach ($aryAdvDepId as $key=>$val) {
            if(in_array($val['adv_dep_id'], $aryPai)) {
                $aryPrior[$val['adv_dep_id']] = $val;
            }
        }
       //$aryPrior = array_values($aryPrior);
        return $aryPrior;
    }
    
    /**
     * 
     * Get random project priority and priority hightest
     * @param Array $advDepIdsPrio: All project priority
     * @param Int $priority: Type priority: = 1-priority; = 2-priority hightest
     * @param Int $limit
     * 
     * @return Array Priority random
     */
    //#686 start maintenance process: luvina company
    public function getPriorIds(&$advDepIdsPrio, $priority=1, $limit=5, $aryNotCmpId=array()) {
    //#686 end maintenance process: luvina company
        
        $aryPriorTemp = array();
        
        if(is_array($advDepIdsPrio)) {
            foreach($advDepIdsPrio as $key => $value) {
                //#686 start maintenance process: luvina company
                if($value['adv_priority'] == $priority && $value['priority_flg'] == 1 && !isset($aryNotCmpId[$value['cmp_id']])) {
                    //#686 end maintenance process: luvina company
                    $aryPriorTemp[$value['cmp_id']][$value['adv_id']][] = $key;
                }
            }
        }
        
        $aryKeyRandCmpId = array();
        if(count($aryPriorTemp) > 0) {
            
            if(count($aryPriorTemp) > $limit) {
                $aryKeyRandCmpId = array_rand($aryPriorTemp, $limit);
                //#686 start maintenance process: luvina company
                if (!is_array($aryKeyRandCmpId)) {
                	$aryKeyRandCmpId = array($aryKeyRandCmpId);
                }
                //#686 end maintenance process: luvina company
            }
            else {
                $aryKeyRandCmpId = array_keys($aryPriorTemp);
            }
        }
        
        //Get key random from all advDepid 
        $aryAdvRandKey = $aryPriorKeys = array();
        if(count($aryKeyRandCmpId) > 0) {
            foreach($aryKeyRandCmpId as $cmp_id) {
                $aryAdvRandKey[$cmp_id] = array_rand($aryPriorTemp[$cmp_id], 1);
            }
            
            foreach($aryAdvRandKey as $cmp_id => $adv_id) {
                $key = array_rand($aryPriorTemp[$cmp_id][$adv_id], 1);
                $aryPriorKeys[] = $aryPriorTemp[$cmp_id][$adv_id][$key];
            }
        }
        
        sort($aryPriorKeys);
        
        //Unset project Prior in array $advDepIdsPrio
        $aryPrior = array();
        if(count($aryPriorKeys) > 0) {
            foreach($aryPriorKeys as $k) {
                $aryPrior[] = $advDepIdsPrio[$k];
                unset($advDepIdsPrio[$k]);
            }
        }
        
        return $aryPrior;
    }
    
    /**
     * 
     * Get information of member
     */
    //#668 start maintance process: Luvina-company #comment:7
    public function getMemInfoForSearchLogined($mem_mail = null, $mem_id = null, $isGetFromDB = false) {
        if(isset($this->mem_data_project_logined) && ($isGetFromDB === false)) {
            return $this->mem_data_project_logined;
        }
        
        if (is_null($mem_mail)) {
            $mem_mail = $this->session->get('MEM_MAIL');
            $mem_id = $this->session->get('MEM_ID');
        }
    //#668 end maintance process: Luvina-company #comment:7
    
        $memData = array();
        
        /* @var $mgrPublicSearch Kj_PublicSearchManager */
        $mgrPublicSearch = $this->backend->getManager("PublicSearch");
        
        /* @var $mgrPublicMagazine Kj_PublicMagazineManager */
        $mgrPublicMagazine = $this->backend->getManager("PublicMagazine");
        //#668 start maintance process: Luvina-company #comment:7
        $mgzUser = $mgrPublicMagazine->getMgzUser( $mem_mail, $mgzId = 3, true);
        if ($mgzUser === $this->config->config['_DB_ERROR']) {
        	return $this->config->config['_DB_ERROR'];
        }
        //#668 end maintance process: Luvina-company #comment:7
        $mgz_usr_id = $mgzUser['mgz_usr_id'];
        
        if($mgz_usr_id) {
            $memData['frm_id'] = Kj_Util::trimAndExplode($mgzUser['mgz_usr_frm_id']);
            $memData['srv_id'] = Kj_Util::trimAndExplode($mgzUser['mgz_usr_srv_id']);
            // get mgz_user_city.mgz_usr_pref_id and mgz_user_city.mgz_usr_city
            $city = $pref = array();
            //#668 start maintance process: Luvina-company #comment:7
            $ret = $mgrPublicMagazine->getWishPrefCityId($pref, $city, $mgz_usr_id, $getAllCityFlg = true, $hash=false);
            if ($ret === $this->config->config['_DB_ERROR']) {
                return $this->config->config['_DB_ERROR'];
            }
            //#668 end maintance process: Luvina-company #comment:7
            
            $memData['pref'] = $pref;
            $memData['city'] = $city;
            
            $mgzUserDao = new MgzUserDao( $this->db, $this->backend );
            $ocp = array();
            $memOcp = array();
            //#668 start maintance process: Luvina-company #comment:7
            $code = $mgzUserDao->findOcpByUsrId($ocp, $mgz_usr_id);
            if($code == $this->config->config['_DB_ERROR']) {
                return $this->config->config['_DB_ERROR'];
                //return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
            //#668 end maintance process: Luvina-company #comment:7
            } else if($code == $this->config->config['_DB_OK']) {
                foreach ($ocp as $value) {
                    $memOcp[] = $value['mgz_usr_ocp_id'];
                }
                
                $memData['pos'] = $mgrPublicSearch->getAdvPositionCondition($memOcp);
            }
        }
        
        $flagGetMeminfo = !is_array($memData['pref']) || count($memData['pref']) == 0;
        $flagGetMeminfo = $flagGetMeminfo || !is_array($memData['city']) || count($memData['city']) == 0;
        $flagGetMeminfo = $flagGetMeminfo || !is_array($memData['frm_id']) || count($memData['frm_id']) == 0;
        
        //#668 start maintance process: Luvina-company #comment:7
        if($flagGetMeminfo && $mem_id > 0) {
        //#668 end maintance process: Luvina-company #comment:7
            $memMasterDao = new MemMasterDao( $this->db, $this->backend );
            
            $cndMem = array(array('mem_id', '=', $mem_id));
            $columnsMem = array('mem_pref_id', 'mem_city_id', 'mem_frm_id');
            $ret = $memMasterDao->getList($info, $cndMem, false, false, $columnsMem);
            if($ret == $this->config->config['_DB_ERROR']) {
                //#668 start maintance process: Luvina-company #comment:7
                return $this->config->config['_DB_ERROR'];
                //return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
                //#668 end maintance process: Luvina-company #comment:7
            }
            if(!is_array($memData['pref']) || count($memData['pref']) == 0) $memData['pref'] = array($info[0]['mem_pref_id']);
            if(!is_array($memData['city']) || count($memData['city']) == 0) $memData['city'] = array($info[0]['mem_city_id']);
            if(!is_array($memData['frm_id']) || count($memData['frm_id']) == 0) $memData['frm_id'] = Kj_Util::trimAndExplode($info[0]['mem_frm_id']);
        }
        
        //#668 start maintance process: Luvina-company #comment:7
        if((!is_array($memData['pos']) || count($memData['pos']) == 0) && $mem_id > 0) {
            
            $aryPos = $this->getPositionByMemId($mem_id);
            if ($aryPos === $this->config->config['_DB_ERROR']) {
                return $this->config->config['_DB_ERROR'];
            }
            
            $memData['pos'] = $aryPos;
            //#668 end maintance process: Luvina-company #comment:7
        }
        
        $this->mem_data_project_logined = $memData;
        
        return $this->mem_data_project_logined;
    }
    
    /**
     * 
     * Get position of member
     * @param Int $mem_id
     * 
     * @return Array position
     */
    public function getPositionByMemId($mem_id) {
        //get ocp by MemSkill
        $mem_skill = $this->getMemSkillbyAryMemId(array($mem_id));
        if($mem_skill == $this->config->config['_DB_ERROR']) {
            //#668 start maintance process: Luvina-company #comment:7
            return $this->config->config['_DB_ERROR'];
            //return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
            //#668 end maintance process: Luvina-company #comment:7
        }
        
        if(isset($mem_skill[$mem_id])) {
            $mem_skill = $mem_skill[$mem_id];
        } else {
            $mem_skill = array();;
        }
        
        if(!is_array($mem_skill) || count($mem_skill) <= 0) return array();
        
        $sysSkillOcpDao = new SysSkillOccupationDao($this->db, $this->backend);
        $ocp = array();
        $condition = array(array('sys_skl_id', 'IN', $mem_skill));
        $code = $sysSkillOcpDao->getOcpidBySklid($ocp, $condition);
        if($code == $this->config->config['_DB_ERROR']) {
            //#668 start maintance process: Luvina-company #comment:7
            return $this->config->config['_DB_ERROR'];
            //return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
            //#668 end maintance process: Luvina-company #comment:7
        }
        
        $memOcp = array();
        foreach ($ocp as $value) {
            $memOcp[$value['sys_ocp_id']] = $value['sys_ocp_id'];
        }
        
        if(!is_array($memOcp) || count($memOcp) <= 0) return array();
        
        /* @var $mgrPublicSearch Kj_PublicSearchManager */
        $mgrPublicSearch = $this->backend->getManager("PublicSearch");
        $pos = $mgrPublicSearch->getAdvPositionCondition($memOcp);
        
        return $pos;
    }
    
    /**
     * 
     * Get near area city
     * @param Array $aryCityId
     * 
     * @return Array Near area city
     */
    //#663 start maintance process: Luvina-company
    function getNearAreaByArrayCityIs($aryCityId, $isGetFromDB = false) {
        if(isset($this->near_city_search_logined) && $isGetFromDB === false) {
            return $this->near_city_search_logined;
        }
        //#663 end maintance process: Luvina-company
        $nearAreaMasterDao = new NearAreaMasterDao($this->db, $this->backend);
        $aryNearCity = array();
        $code = $nearAreaMasterDao->getNearAreaByArrayCityIs($aryNearCity, $aryCityId);
        if($code == $this->config->config['_DB_ERROR']) {
            //#668 start maintance process: Luvina-company #comment:7
            return $this->config->config['_DB_ERROR'];
            //return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
            //#668 end maintance process: Luvina-company #comment:7
        }
        //#686 start maintance process: Luvina-company
        $aryNearCity = array_values($aryNearCity);
        //#686 end maintance process: Luvina-company
        
        $this->near_city_search_logined = $aryNearCity;
        
        return $this->near_city_search_logined;
    }
    
    /**
     * Step 8
     * Get history apply in $numDate last day
     * @param Array $cndFromInput
     * @param Int $numDate
     * 
     * @return array adv_dep_id
     */
    //#668 start maintance process: Luvina-company
    public function getHistoryApply($cndFromInput, $numDate, $mem_id = null) {
    	
        //Append cond
        $cndFromInput[_PUBLIC_SEARCH_CONDITION_MEM_ID] = (is_null($mem_id)) ? $this->session->get('MEM_ID') : $mem_id;
        if(!$cndFromInput[_PUBLIC_SEARCH_CONDITION_MEM_ID]) {
            return array();
        }
    //#668 end maintance process: Luvina-company
        $cndFromInput[_PUBLIC_SEARCH_CONDITION_MEM_APPLY_DAY] = $numDate;
        
        $aryParams = array();
        $strSQL = $this->makeSqlProjectSearchLogined($aryParams, _PUBLIC_SEARCH_LOGINED_PATTERN_HISTORY_APPLY, $cndFromInput);
    
        $result = $this->db->db->getAll($strSQL, $aryParams, DB_FETCHMODE_ASSOC);
        if (Ethna::isError($result)) {
            $this->logger->log(LOG_EMERG, print_r($result->getMessage(), true));
            //#668 start maintance process: Luvina-company #comment:7
            return $this->config->config['_DB_ERROR'];
            //return Ethna::raiseError("DBエラーです", E_DB_ERROR);
            //#668 end maintance process: Luvina-company #comment:7
        }
        
        $aryData = array();
        foreach ($result as $key=>$value) {
        	$aryData[] = (int)$value['adv_dep_id'];
        	unset($result[$key]);
        }
        
        return $aryData;
    }
    
    /**
     * 
     * Get total records satisfy condition exclude adv_dep_id applied 45 last day 
     * @param Array $cndFromInput: array conditon search get from $form
     * @param Array $aryAdvDepIdHistory: array adv_dep_id apply 45 last day
     * 
     * @return Int total records
     */
    public function getCountSearchLogined($cndFromInput, $aryAdvDepIdHistory) {
        
        //Append cond
        if(is_array($aryAdvDepIdHistory) && count($aryAdvDepIdHistory)) {
            $cndFromInput[_PUBLIC_SEARCH_CONDITION_NOT_IN_ADV_DEP_IDS] = array_values($aryAdvDepIdHistory);
        }
        
        $aryParams = array();
        $strSQL = $this->makeSqlProjectSearchLogined($aryParams, _PUBLIC_SEARCH_LOGINED_PATTERN_COUNT, $cndFromInput);
    
        $result = $this->db->db->getAll($strSQL, $aryParams, DB_FETCHMODE_ASSOC);
        if (Ethna::isError($result)) {
            $this->logger->log(LOG_EMERG, print_r($result->getMessage(), true));
            //#668 start maintance process: Luvina-company #comment:7
            return $this->config->config['_DB_ERROR'];
            //return Ethna::raiseError("DBエラーです", E_DB_ERROR);
            //#668 end maintance process: Luvina-company #comment:7
        }
        
        return $result[0]['cnt'];
    }
    
    /**
     * 
     * Get all project priority
     * @param Array $cndFromInput: array conditon search get from $form
     * @param Array $aryAdvDepIdHistory: array adv_dep_id apply 45 last day
     * 
     * @return Array project priority
     */
    //#686 Start maintance process: Luvina-company:#comment:17 
    //#700 Start maintance process: Luvina-company
    public function getProjectPrio($cndFromInput, $aryAdvDepIdHistory, $mgzMailFlg = false, $cndUser = array()) {
    //#700 end maintance process: Luvina-company
    	//#686 end maintance process: Luvina-company:#comment:17 
        //Append cond
        if(is_array($aryAdvDepIdHistory) && count($aryAdvDepIdHistory)) {
            $cndFromInput[_PUBLIC_SEARCH_CONDITION_NOT_IN_ADV_DEP_IDS] = array_values($aryAdvDepIdHistory);
        }
        
        $aryParams = array();
        //#686 Start maintance process: Luvina-company:#comment:17 
        //#700 Start maintance process: Luvina-company
        $strSQL = $this->makeSqlProjectSearchLogined($aryParams, _PUBLIC_SEARCH_LOGINED_PATTERN_PRIOR, $cndFromInput, $cndUser, $mgzMailFlg);
        //#700 end maintance process: Luvina-company
        //#686 end maintance process: Luvina-company:#comment:17 
        $result = $this->db->db->getAll($strSQL, $aryParams, DB_FETCHMODE_ASSOC);
        if (Ethna::isError($result)) {
            $this->logger->log(LOG_EMERG, print_r($result->getMessage(), true));
            //#668 start maintance process: Luvina-company 
            return $this->config->config['_DB_ERROR'];
            //return Ethna::raiseError("DBエラーです", E_DB_ERROR);
            //#668 end maintance process: Luvina-company
        }
        
        $aryData = array();
        foreach ($result as $key=>$value) {
            $aryData[] = array(
                            'adv_dep_id' => (int)$value['adv_dep_id'],
                            'adv_id' => (int)$value['adv_id'],
                            'cmp_id' => (int)$value['cmp_id'],
                            'adv_priority' => (int)$value['adv_priority'],
                            'priority_flg' => (int)$value['priority_flg']
                        );
            unset($result[$key]);
        }
        
        return $aryData;
    }
    
    /**
     * 
     * Get adv_dep_id of each pattern
     * @param Array $cndFromInput: array conditon search get from $form
     * @param Array $cndUser: array condition search of user get from DB
     * @param Array $aryAdvDepIdHistory: array adv_dep_id apply 45 last day
     * @param Array $aryAdvDepIds
     * @param Int $pattern
     * @param Int $limitOfPattern
     * 
     * @return Array adv_dep_id of pattern
     */
    public function getProjectByPatternSearch(
                                                $cndFromInput, 
                                                $cndUser, 
                                                $aryAdvDepIdHistory, 
                                                $aryAdvDepIds, 
                                                $pattern,
                                                $limitOfPattern
                                                //#663 Start maintance process: Luvina-company:
                                                ,$mgzMailFlg = false
                                                //#663 End maintance process: Luvina-company:
                                                ) {
        //Append cond
        $aryNotInAdvDepId = array();
        if(is_array($aryAdvDepIdHistory) && count($aryAdvDepIdHistory)) {
            $aryNotInAdvDepId = array_merge($aryNotInAdvDepId, $aryAdvDepIdHistory);
        }
        if(is_array($aryAdvDepIds) && count($aryAdvDepIds)) {
            $aryNotInAdvDepId = array_merge($aryNotInAdvDepId, $aryAdvDepIds);
        }
        if(is_array($aryNotInAdvDepId) && count($aryNotInAdvDepId)) {
            $cndFromInput[_PUBLIC_SEARCH_CONDITION_NOT_IN_ADV_DEP_IDS] = array_values($aryNotInAdvDepId);
        } else {
            $cndFromInput[_PUBLIC_SEARCH_CONDITION_NOT_IN_ADV_DEP_IDS] = null;
        }
        
        $aryParams = array();
        //#663 Start maintance process: Luvina-company:
        $strSQL = $this->makeSqlProjectSearchLogined($aryParams, $pattern, $cndFromInput, $cndUser, $mgzMailFlg);
        //#663 End maintance process: Luvina-company:
        
        if (is_numeric($limitOfPattern) && $limitOfPattern > 0) {
            $strSQL .= " LIMIT {$limitOfPattern} ";
        }
        
        $result = $this->db->db->getAll($strSQL, $aryParams, DB_FETCHMODE_ASSOC);
        if (Ethna::isError($result)) {
            $this->logger->log(LOG_EMERG, print_r($result->getMessage(), true));
            //#668 start maintance process: Luvina-company #comment:7
            return $this->config->config['_DB_ERROR'];
            //return Ethna::raiseError("DBエラーです", E_DB_ERROR);
            //#668 end maintance process: Luvina-company #comment:7
        }
        
        $aryData = array();
        foreach ($result as $key=>$value) {
            $aryData[] = (int)$value['adv_dep_id'];
            unset($result[$key]);
        }
        return $aryData;
    }
    
    /**
     * 
     * Get condition of each pattern
     * @param Int $pattern
     * 
     * @return Array Condtion of pattern $pattern
     */
    //#668 start maintance process: Luvina-company #comment:7
    //#663 start maintance process: Luvina-company
    public function getConditionPattern($pattern, $mem_mail = null, $mem_id = null, $isGetFromDB = false, $mgzMailFlg = false, $dataUser = array()) {
    //#663 end maintance process: Luvina-company
    //#668 end maintance process: Luvina-company #comment:7
        $cndUser = array();
        $cndUser[_PUBLIC_SEARCH_CONDITION_PREF] = null;
        $cndUser[_PUBLIC_SEARCH_CONDITION_CITY] = null;
        $cndUser[_PUBLIC_SEARCH_CONDITION_POSITION] = null;
        $cndUser[_PUBLIC_SEARCH_CONDITION_FORM] = null;
        $cndUser[_FEATURE_SEARCH_CONDITION_SERVICE] = null;
        
        if($pattern == _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER) {
            return $cndUser;
        }
        
        //#668 start maintance process: Luvina-company #comment:7
        //#663 start maintance process: Luvina-company
        if ($mgzMailFlg === false) {
	        $dataUser = $this->getMemInfoForSearchLogined($mem_mail, $mem_id, $isGetFromDB);
	        if ($dataUser === $this->config->config['_DB_ERROR']) {
	            return $this->config->config['_DB_ERROR'];
	        }
        }
        //#663 end maintance process: Luvina-company
        //#668 end maintance process: Luvina-company #comment:7
        //#663 start maintance process: Luvina-company
        $aryCityNear = array();
        //#895 start maintance process: Luvina-company:【Bug ID: 2614】 comment:15
        // remove emtyp data of frm_id and set frm_id = array(1,2)
        $frmIds = $this->getFrmOfPattern($dataUser['frm_id']);
        if ($frmIds === $this->config->config['_DB_ERROR']) {
            return $this->config->config['_DB_ERROR'];
        }
        $dataUser['frm_id'] = $frmIds;
        
        $aryPrefNear = null;
        //#895 end maintance process: Luvina-company:【Bug ID: 2614】 comment:15
        if(is_array($dataUser['city']) && count($dataUser['city'])) {
	        $aryCityNear = $this->getNearAreaByArrayCityIs($dataUser['city'], $isGetFromDB);
	        if ($aryCityNear === $this->config->config['_DB_ERROR']) {
	        	return $this->config->config['_DB_ERROR'];
	        }
            //#895 start maintance process: Luvina-company:【Bug ID: 2614】 comment:15
            $aryPrefNear = $this->getPrefOfCityNotNearArea($dataUser['city'], $isGetFromDB);
            if ($aryPrefNear === $this->config->config['_DB_ERROR']) {
                return $this->config->config['_DB_ERROR'];
            }
            //#895 end maintance process: Luvina-company:【Bug ID: 2614】 comment:15
        }
        //#663 end maintance process: Luvina-company
        $cndUser[_PUBLIC_SEARCH_CONDITION_POSITION] = $dataUser['pos'];

        //#889 start maintenance process: Luvina-company
        if ($mgzMailFlg === false) {
            if (is_null($mem_id)) {
                $mem_id = $this->session->get('MEM_ID');
            }
            if($mem_id > 0) {
                $mem_skill = $this->getMemSkillbyAryMemId(array($mem_id));
                if($mem_skill == $this->config->config['_DB_ERROR']) {
                    return $this->config->config['_DB_ERROR'];
                }
                
                $aryMemSkill = (isset($mem_skill[$mem_id])) ? $mem_skill[$mem_id] : array();
                if(count($aryMemSkill) == 1 && $aryMemSkill[0] == 12) {
                    $cndUser[_PUBLIC_SEARCH_CONDITION_POSITION] = null;
                }
            }
        }
        //#889 end maintenance process: Luvina-company

        switch ($pattern) {
            case _PUBLIC_SEARCH_LOGINED_PATTERN_1:
                    $cndUser[_PUBLIC_SEARCH_CONDITION_PREF] = $dataUser['pref'];
                    $cndUser[_PUBLIC_SEARCH_CONDITION_CITY] = $dataUser['city'];
                    $cndUser[_PUBLIC_SEARCH_CONDITION_FORM] = $dataUser['frm_id'];
                    $cndUser[_FEATURE_SEARCH_CONDITION_SERVICE] = $dataUser['srv_id'];
                break;
            case _PUBLIC_SEARCH_LOGINED_PATTERN_2:
                    $cndUser[_PUBLIC_SEARCH_CONDITION_PREF] = $dataUser['pref'];
                    $cndUser[_PUBLIC_SEARCH_CONDITION_CITY] = $dataUser['city'];
                    $cndUser[_PUBLIC_SEARCH_CONDITION_FORM] = $dataUser['frm_id'];
                break;
            case _PUBLIC_SEARCH_LOGINED_PATTERN_3:
                    if(is_array($dataUser['city']) && count($dataUser['city'])) {
                        //#668 start maintance process: Luvina-company #comment:7
                    	//#663 start maintance process: Luvina-company
                        /*
                    	$aryCityNear = $this->getNearAreaByArrayCityIs($dataUser['city']);
                        if ($aryCityNear === $this->config->config['_DB_ERROR']) {
                            return $this->config->config['_DB_ERROR'];
                        }
                        */
                        //#663 end maintance process: Luvina-company
                        $cndUser[_PUBLIC_SEARCH_CONDITION_CITY] = $aryCityNear; // near city
                        //#668 end maintance process: Luvina-company #comment:7
                    }
                    //#895 start maintance process: Luvina-company:【Bug ID: 2614】 comment:15
                    $cndUser[_PUBLIC_SEARCH_CONDITION_PREF_NEAR_AREA] = $aryPrefNear;
                    //#895 end maintance process: Luvina-company:【Bug ID: 2614】 comment:15
                    $cndUser[_PUBLIC_SEARCH_CONDITION_FORM] = $dataUser['frm_id'];
                    $cndUser[_FEATURE_SEARCH_CONDITION_SERVICE] = $dataUser['srv_id'];
                break;
            case _PUBLIC_SEARCH_LOGINED_PATTERN_4:
                    if(is_array($dataUser['city']) && count($dataUser['city'])) {
                        //#668 start maintance process: Luvina-company #comment:7
                    	//#663 start maintance process: Luvina-company
                        /*
                        $aryCityNear = $this->getNearAreaByArrayCityIs($dataUser['city']);
                        if ($aryCityNear === $this->config->config['_DB_ERROR']) {
                            return $this->config->config['_DB_ERROR'];
                        }
                        */
                        //#663 end maintance process: Luvina-company
                        $cndUser[_PUBLIC_SEARCH_CONDITION_CITY] = $aryCityNear; // near city
                        //#668 end maintance process: Luvina-company #comment:7
                    }
                    //#895 start maintance process: Luvina-company:【Bug ID: 2614】 comment:15
                    $cndUser[_PUBLIC_SEARCH_CONDITION_PREF_NEAR_AREA] = $aryPrefNear;
                    //#895 end maintance process: Luvina-company:【Bug ID: 2614】 comment:15
                    $cndUser[_PUBLIC_SEARCH_CONDITION_FORM] = $dataUser['frm_id'];
                break;
            case _PUBLIC_SEARCH_LOGINED_PATTERN_5:
                    $cndUser[_PUBLIC_SEARCH_CONDITION_PREF] = $dataUser['pref'];
                    $cndUser[_PUBLIC_SEARCH_CONDITION_CITY] = $dataUser['city'];
                break;
            case _PUBLIC_SEARCH_LOGINED_PATTERN_6:
                    if(is_array($dataUser['city']) && count($dataUser['city'])) {
                        //#668 start maintance process: Luvina-company #comment:7
                    	//#663 start maintance process: Luvina-company
                        /*
                        $aryCityNear = $this->getNearAreaByArrayCityIs($dataUser['city']);
                        if ($aryCityNear === $this->config->config['_DB_ERROR']) {
                            return $this->config->config['_DB_ERROR'];
                        }
                        */
                        //#663 end maintance process: Luvina-company
                        $cndUser[_PUBLIC_SEARCH_CONDITION_CITY] = $aryCityNear; // near city
                        //#668 end maintance process: Luvina-company #comment:7
                    }
                    //#895 start maintance process: Luvina-company:【Bug ID: 2614】 comment:15
                    $cndUser[_PUBLIC_SEARCH_CONDITION_PREF_NEAR_AREA] = $aryPrefNear;
                    //#895 end maintance process: Luvina-company:【Bug ID: 2614】 comment:15
                break;
                
            case _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER:
            default:
                break;
        }
        
        return $cndUser;
    }
    
    
    //#895 start maintance process: Luvina-company:【Bug ID: 2614】 comment:15
    public function getFrmOfPattern($aryFrmIds) {
        if(is_array($aryFrmIds) && count($aryFrmIds) > 0) {
            $aryFrmIds = Kj_Util::removeEmptyOfArray($aryFrmIds);
            if(empty($aryFrmIds)) {
                if(isset($this->_all_sys_frm_id)) {
                    $aryFrmIds = $this->_all_sys_frm_id;
                } else {
                    $dao = new SysFormDao($this->db, $this->backend);
                    $resultSysForm = null;
                    $ret = $dao->getList($resultSysForm, false, false, false, $columns = array('sys_frm_id'));
                    if($ret == $this->config->config['_DB_ERROR']) {
                        return $ret;
                    }
                    $aryAllFrmIds = array();
                    foreach ($resultSysForm as $frmId) {
                        $aryAllFrmIds[] = $frmId['sys_frm_id'];
                    }
                    $aryFrmIds = $this->_all_sys_frm_id = $aryAllFrmIds;
                }
            }
        }

        return $aryFrmIds;
    }
    /**
     * 
     * @param $aryCityIds: array city ids
     * @return array (pref_id)
     */
    public function getPrefOfCityNotNearArea($aryCityIds, $isGetFromDB = false) {
        if(isset($this->near_pref_search_logined) && $isGetFromDB === false) {
            return $this->near_pref_search_logined;
        }

        if(!is_array($aryCityIds) || !$aryCityIds) {
            $this->near_pref_search_logined = null;
            return $this->near_pref_search_logined;
        }
        $nearAreaMasterDao = new NearAreaMasterDao($this->db, $this->backend);
        $aryNearCity = array();
        $code = $nearAreaMasterDao->getCityNearAreaByArrayCityIs($aryNearCity, $aryCityIds);
        if($code == $this->config->config['_DB_ERROR']) {
            return $this->config->config['_DB_ERROR'];
        }

        // get pref of all city
        $sysAreCityDao = new SysAreCityDao($this->db, $this->backend);
        $condition[] = array('sys_are_city_id', 'IN', array_values($aryCityIds));
        $result = array();
        $code = $sysAreCityDao->getList(
                    $result, $condition, $sort = false, $getRow = false, 
                    $columns = array('sys_are_city_id', 'sys_are_city_pref'));
        if($code == $this->config->config['_DB_ERROR']) {
            return $this->config->config['_DB_ERROR'];
        }
        
        $aryAllCity = array();
        foreach ($result as $info) {
            $aryAllCity[$info['sys_are_city_id']] = $info['sys_are_city_pref'];
        }
        
        $aryPrefNotNearArea = array();
        $aryPrefHaveNearArea = array();
        foreach ($aryCityIds as $cityId) {
            $pref = $aryAllCity[$cityId];
            if(isset($aryNearCity[$cityId])) {
                $aryPrefHaveNearArea[$pref] = $pref;
            } else {
                $aryPrefNotNearArea[$pref] = $pref;
            }
        }
        
        $lisrPref = null;
        foreach ($aryPrefNotNearArea as $pref) {
            if(!isset($aryPrefHaveNearArea[$pref])) {
                $lisrPref[] = $pref;
            }
        }
        
        $this->near_pref_search_logined = $lisrPref;
        
        return $this->near_pref_search_logined;
        
    }
    //#895 end maintance process: Luvina-company:【Bug ID: 2614】 comment:15
    
    /**
     * 
     * Check pattern
     * @param Int $pattern = _PUBLIC_SEARCH_LOGINED_PATTERN_1,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_2,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_3,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_4,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_5,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_6,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER
     * @param Array $cndFromInput: array conditon search get from $form
     * @param Array $cndUser: array condition search of user get from DB
     * 
     * @return Bool: = TRUE is $pattern; = FALSE no is $pattern. 
     */
    public function checkConditionPattern($pattern, $cndFromInput = array(), $cndUser = array()) {
        if($pattern == _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER) {
            return true;
        } 
        // check PREF
        if(is_array($cndFromInput[_PUBLIC_SEARCH_CONDITION_PREF]) && is_array($cndUser[_PUBLIC_SEARCH_CONDITION_PREF])) {
            $flg = Kj_Util::checkArrayInArray(
                        $cndUser[_PUBLIC_SEARCH_CONDITION_PREF],
                        $cndFromInput[_PUBLIC_SEARCH_CONDITION_PREF]
            );
            if(!$flg) return false;
        }
        // check City
        if(is_array($cndFromInput[_PUBLIC_SEARCH_CONDITION_CITY]) && is_array($cndUser[_PUBLIC_SEARCH_CONDITION_CITY])) {
            $flg = Kj_Util::checkArrayInArray(
                        $cndUser[_PUBLIC_SEARCH_CONDITION_CITY],
                        $cndFromInput[_PUBLIC_SEARCH_CONDITION_CITY]
            );
            if(!$flg) return false;
        }
        // check FORM
        //#663 start maintance process: Luvina-company
        /*
        if(is_array($cndFromInput[_PUBLIC_SEARCH_CONDITION_FORM]) && is_array($cndUser[_PUBLIC_SEARCH_CONDITION_FORM])) {
            $flg = Kj_Util::checkArrayInArray(
                        $cndUser[_PUBLIC_SEARCH_CONDITION_FORM],
                        $cndFromInput[_PUBLIC_SEARCH_CONDITION_FORM]
            );
            if(!$flg) return false;
        }
        */
        //#663 end maintance process: Luvina-company
        
        // check SERVICE
        if(is_array($cndFromInput[_FEATURE_SEARCH_CONDITION_SERVICE]) && is_array($cndUser[_FEATURE_SEARCH_CONDITION_SERVICE])) {
            $flg = Kj_Util::checkArrayInArray(
                        $cndUser[_FEATURE_SEARCH_CONDITION_SERVICE],
                        $cndFromInput[_FEATURE_SEARCH_CONDITION_SERVICE]
            );
            if(!$flg) return false;
        }
        // check POSITION
        if(is_array($cndFromInput[_PUBLIC_SEARCH_CONDITION_POSITION]) && is_array($cndUser[_PUBLIC_SEARCH_CONDITION_POSITION])) {
            $flg = Kj_Util::checkArrayInArray(
                        $cndUser[_PUBLIC_SEARCH_CONDITION_POSITION],
                        $cndFromInput[_PUBLIC_SEARCH_CONDITION_POSITION]
            );
            if(!$flg) return false;
        }
        return true;
    }
    
    /**
     * 
     * Make column select in script sql for each pattern
     * @param Array $aryColumns: array column
     * @param Int $pattern = _PUBLIC_SEARCH_LOGINED_PATTERN_1,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_2,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_3,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_4,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_5,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_6,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER
     * @param Array $cndFromInput: array conditon search get from $form
     * @param Array $cndUser: array condition search of user get from DB
     * 
     */
    //#686 Start maintance process: Luvina-company:#comment:17 
    public function makeSelectProjectSearchLogined(&$aryColumns, $pattern, $cndFromInput = array(), $cndUser = array(), $mgzMailFlg = false) {
    	//#686 end maintance process: Luvina-company:#comment:17 
        switch ($pattern) {
            case _PUBLIC_SEARCH_LOGINED_PATTERN_COUNT:
                $aryColumns[] = 'COUNT(DISTINCT adv_department.adv_dep_id) as cnt';
                break;
            case _PUBLIC_SEARCH_LOGINED_PATTERN_PRIOR:
                $aryColumns[] = 'DISTINCT adv_department.adv_dep_id';
                $aryColumns[] = 'adv_master.adv_id';
                $aryColumns[] = 'cmp_department.cmp_id';
                //#686 Start maintance process: Luvina-company:#comment:17 
                if ($mgzMailFlg) {
	                $aryColumns[] = 'adv_master.adv_priority_mgz AS adv_priority';
	                //#754 Start maintance process: Luvina-company
	                $aryColumns[] = 'IF(
	                                    DATE(adv_master.adv_priority_mgz_date_started) <> DATE("1900-01-01 00:00:00") 
	                                    || DATE(adv_master.adv_priority_mgz_date_expired) <> DATE("1900-01-01 00:00:00"
	                                 ), 
	                                    IF(
	                                        (DATE(adv_master.adv_priority_mgz_date_started) <= DATE(NOW())) 
	                                        && (DATE(NOW()) <= DATE(adv_master.adv_priority_mgz_date_expired)
	                                    ), 1, 0
	                                 ),1) as priority_flg';
	                //#754 e maintance process: Luvina-company
                } else {
	                $aryColumns[] = 'adv_master.adv_priority';
	                $aryColumns[] = 'IF(
	                                    DATE(adv_master.adv_priority_date_started) <> DATE("1900-01-01 00:00:00") 
	                                    || DATE(adv_master.adv_priority_date_expired) <> DATE("1900-01-01 00:00:00"
	                                 ), 
	                                    IF(
	                                        (DATE(adv_master.adv_priority_date_started) <= DATE(NOW())) 
	                                        && (DATE(NOW()) <= DATE(adv_master.adv_priority_date_expired)
	                                    ), 1, 0
	                                 ),1) as priority_flg';
                }
                //#686 end maintance process: Luvina-company:#comment:17 
                break;
            default:
                $aryColumns[] = 'DISTINCT adv_department.adv_dep_id';
                break;
        }
    }
    
    /**
     * 
     * Make table and table join in script sql for each pattern
     * @param Array &$aryFroms: array table join
     * @param Int $pattern = _PUBLIC_SEARCH_LOGINED_PATTERN_1,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_2,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_3,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_4,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_5,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_6,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER
     * @param Array $cndFromInput: array conditon search get from $form
     * @param Array $cndUser: array condition search of user get from DB
     * 
     */
    public function makeFromsProjectSearchLogined(&$aryFroms, $pattern, $conditionMap = array(), $cndUser = array()) {
        $aryFroms[] = 'adv_master';
        $aryFroms[] = 'INNER JOIN adv_department ON adv_master.adv_id=adv_department.adv_id';
        $aryFroms[] = 'INNER JOIN cmp_department ON cmp_department.cmp_dep_id=adv_department.cmp_dep_id';
        $aryFroms[] = 'INNER JOIN cmp_master ON cmp_master.cmp_id = cmp_department.cmp_id';
        
        //#663 start maintance process: Luvina-company
        /*
        $isForm = $conditionMap[_PUBLIC_SEARCH_CONDITION_FORM] !== false;
        $isForm = $isForm || $cndUser[_PUBLIC_SEARCH_CONDITION_FORM] != false;
        if($isForm) {
            $aryFroms[] = 'INNER JOIN adv_form ON adv_master.adv_id=adv_form.adv_id';
        }
        */
        //#663 end maintance process: Luvina-company
        
        $isFocus = $conditionMap[_PUBLIC_SEARCH_CONDITION_FOCUS] !== false;
        if($isFocus) {
            $aryFroms[] = 'INNER JOIN adv_focus ON adv_master.adv_id=adv_focus.adv_id';
        }
        
        $station = $conditionMap[_PUBLIC_SEARCH_CONDITION_STATION];
        if(is_array($station)) {
            $aryFroms[] = 'INNER JOIN cmp_dep_rai_station ON adv_department.cmp_dep_id=cmp_dep_rai_station.cmp_dep_id';
        } else {
            $dstr = $conditionMap[_FEATURE_SEARCH_CONDITION_DSTR];
            if(is_numeric($dstr)) {
                $aryFroms[] = 'INNER JOIN sys_are_city ON cmp_department.cmp_dep_city=sys_are_city.sys_are_city_id';
                $conditionMap[_PUBLIC_SEARCH_CONDITION_PREF] = false;
            }
            $rai = $conditionMap[_PUBLIC_SEARCH_CONDITION_RAIL];
            //#680 start maintance process: Luvina-company
            $raiStation = $conditionMap[_PUBLIC_SEARCH_CONDITION_RAISTATION];
            if(is_array($rai) && count($rai) > 0) {
                $aryFroms[] = 'INNER JOIN cmp_dep_rai_station ON cmp_department.cmp_dep_id=cmp_dep_rai_station.cmp_dep_id';
            } elseif (is_array($raiStation) && count($raiStation) > 0) {
            	$aryFroms[] = 'LEFT JOIN cmp_dep_rai_station ON cmp_department.cmp_dep_id=cmp_dep_rai_station.cmp_dep_id';
            }
            //#680 end maintance process: Luvina-company
        }
        
        if($pattern == _PUBLIC_SEARCH_LOGINED_PATTERN_HISTORY_APPLY) {
            $aryFroms[] = 'INNER JOIN mem_apply ON mem_apply.mem_app_adv_id = adv_department.adv_id AND mem_apply.mem_app_dep_id = adv_department.cmp_dep_id';
        }
        //#884 start maintance process: Luvina-company
        $isAdvNotPrice = $cndUser[_PUBLIC_SEARCH_CONDITION_NOT_ADV_PRICE] !== false;
        //#890 start maintance process: Luvina-company
        if(!$isAdvNotPrice) {
            $isAdvNotPrice = $conditionMap[_PUBLIC_SEARCH_CONDITION_NOT_ADV_PRICE] !== false;
        }
        //#890 end maintance process: Luvina-company
        //#890 start maintance process: Luvina-company #comment:5
        $isAdvEqPrice = $conditionMap[_PUBLIC_SEARCH_CONDITION_EQUAL_ADV_PRICE] !== false;
        if($isAdvNotPrice || $isAdvEqPrice) {
        //#890 end maintance process: Luvina-company #comment:5
            $aryFroms[] = 'LEFT JOIN adv_nominal_price ON  
                          (adv_nominal_price.adv_nml_adv_id = 0 
                          AND adv_nominal_price.adv_nml_cmp_id = cmp_master.cmp_id )';
        }
        //#884 end maintance process: Luvina-company
    }
    
    /**
     * 
     * Make condition where in script sql for each pattern
     * @param Array &$aryParams: array params binding
     * @param Array &$aryCondition: array condition of clause where
     * @param Int $pattern = _PUBLIC_SEARCH_LOGINED_PATTERN_1,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_2,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_3,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_4,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_5,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_6,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER
     * @param Array $cndFromInput: array conditon search get from $form
     * @param Array $cndUser: array condition search of user get from DB
     * 
     */
    //#686 Start maintance process: Luvina-company:#comment:17
    public function makeWhereProjectSearchLogined(&$aryParams, &$aryCondition, $pattern, $conditionMap = array(), $cndUser = array(), $mgzMailFlg = false) {
    	//#686 end maintance process: Luvina-company:#comment:17
        $sysPosIds = $conditionMap[_PUBLIC_SEARCH_CONDITION_POSITION];
        if(is_array($sysPosIds) && count($sysPosIds)) {
            $aryCondition[] = 'adv_master.adv_position IN (' . join(', ', array_fill(0, count($sysPosIds), '?')) . ')';
            $aryParams = array_merge($aryParams, array_values($sysPosIds));
        }

        $advForm = $conditionMap[_PUBLIC_SEARCH_CONDITION_FORM];
        if(is_array($advForm) && count($advForm)) {
            //#663 start maintance process: Luvina-company
            /*
            $aryCondition[] = 'adv_form.adv_form IN (' . join(', ', array_fill(0, count($advForm), '?')) . ')';
            */
        	$aryCondition[] = 'EXISTS(SELECT 1 FROM adv_form WHERE adv_form.adv_id=adv_master.adv_id AND adv_form.adv_form IN(' . join(', ', array_fill(0, count($advForm), '?')) . '))';
            //#663 start maintance process: Luvina-company
            $aryParams = array_merge($aryParams, array_values($advForm));
        }

        $advFocus = $conditionMap[_PUBLIC_SEARCH_CONDITION_FOCUS];
        if(is_array($advFocus) && count($advFocus)) {
            $aryCondition[] = 'adv_focus.adv_foc_label IN (' . join(', ', array_fill(0, count($advFocus), '?')) . ')';
            $aryParams = array_merge($aryParams, array_values($advFocus));
        }
        // #442 Begin modify
        $station = $conditionMap[_PUBLIC_SEARCH_CONDITION_STATION];
        if(is_array($station) && count($station)) {
            $aryCondition[] = 'cmp_dep_rai_station.sys_rai_sta_id IN (' . join(', ', array_fill(0, count($station), '?')) . ')';
            $aryParams = array_merge($aryParams, array_values($station));
        } else {
            // 特集求人用地方
            $dstr = $conditionMap[_FEATURE_SEARCH_CONDITION_DSTR];
            if(is_numeric($dstr)) {
                $aryCondition[] = 'sys_are_city.sys_are_city_pref IN (SELECT sys_pref_id FROM sys_prefecture WHERE sys_pref_district=?)';
                $aryParams = array_merge($aryParams, array($dstr));
                
                $conditionMap[_PUBLIC_SEARCH_CONDITION_PREF] = false;
            }
    
            // KJ_RENEWAL:都道府県複数選択可
            $pref = $conditionMap[_PUBLIC_SEARCH_CONDITION_PREF];
            $cmpDepCities = $conditionMap[_PUBLIC_SEARCH_CONDITION_CITY];
            //#680 start maintance process: Luvina-company
            $aryConditionTmp = array();
            //#680 end maintance process: Luvina-company
            //#895 start maintance process: Luvina-company : #comment 15
            $prefNearArea = array();
            if(isset($conditionMap[_PUBLIC_SEARCH_CONDITION_PREF_NEAR_AREA])) {
                $prefNearArea = $conditionMap[_PUBLIC_SEARCH_CONDITION_PREF_NEAR_AREA];
            } 
            if((is_array($pref) && count($pref) > 0) || (is_array($prefNearArea) && count($prefNearArea)) > 0 || (is_array($cmpDepCities) && count($cmpDepCities) > 0)) {
            //#895 end maintance process: Luvina-company : #comment 15
            	//#680 start maintance process: Luvina-company
                //$aryConditionTmp = array();
                //#680 end maintance process: Luvina-company
                //#668 start maintance process: Luvina-company
                //#895 start maintance process: Luvina-company : #comment 15
                if(is_array($prefNearArea) && count($prefNearArea) > 0) {
                    $aryConditionTmp[] = 'cmp_department.cmp_dep_pref IN (' . join(', ', array_fill(0, count($prefNearArea), '?')) . ')';
                    $aryParams = array_merge($aryParams, array_values($prefNearArea));
                } else
                //#895 end maintance process: Luvina-company : #comment 15
                if(is_array($pref)  && count($pref) > 0) {
                    //#668 end maintance process: Luvina-company
                    $aryConditionTmp[] = 'cmp_department.cmp_dep_pref IN (' . join(', ', array_fill(0, count($pref), '?')) . ')';
                    $aryParams = array_merge($aryParams, array_values($pref));
                }
               
                //$cmpDepCities = $conditionMap[_PUBLIC_SEARCH_CONDITION_CITY];
                //#754 start maintance process: Luvina-company: Fixbug warning
                if(isset($cmpDepCities[0]) && is_numeric($cmpDepCities[0])) {
                    //#754 end maintance process: Luvina-company
                    //#895 start maintance process: Luvina-company : #comment 15
                    if((is_array($pref) && count($pref) > 0) || (is_array($prefNearArea) && count($prefNearArea) > 0)) {
                    //#895 start maintance process: Luvina-company : #comment 15
                       $aryConditionTmp[] = 'OR cmp_department.cmp_dep_city IN (' . join(', ', array_fill(0, count($cmpDepCities), '?')) . ')';
                    } else {
                       $aryConditionTmp[] = 'cmp_department.cmp_dep_city IN (' . join(', ', array_fill(0, count($cmpDepCities), '?')) . ')';
                    }
                    $aryParams = array_merge($aryParams, array_values($cmpDepCities));
                }
                
                //#680 start maintance process: Luvina-company
                /*
                if(count($aryConditionTmp)) {
                    //#668 start maintance process: Luvina-company 
                    $aryCondition[] = '(' . join(" ", $aryConditionTmp) . ')';
                    //#668 end maintance process: Luvina-company
                }
                */
                //#680 end maintance process: Luvina-company
            }
            
            //#680 start maintance process: Luvina-company
            $raiStation = $conditionMap[_PUBLIC_SEARCH_CONDITION_RAISTATION];
            if (is_array($raiStation) && count($raiStation) > 0) {
            	if(is_array($aryConditionTmp) && count($aryConditionTmp) > 0) {
            		$aryConditionTmp[] = 'OR cmp_dep_rai_station.sys_rai_sta_id IN (' . join(', ', array_fill(0, count($raiStation), '?')) . ')';
            	} else {
            		$aryConditionTmp[] = ' cmp_dep_rai_station.sys_rai_sta_id IN (' . join(', ', array_fill(0, count($raiStation), '?')) . ')';
            	}
            	$aryParams = array_merge($aryParams, array_values($raiStation));
            }
            
            if(count($aryConditionTmp)) {
            	//#668 start maintance process: Luvina-company
            	$aryCondition[] = '(' . join(" ", $aryConditionTmp) . ')';
            	//#668 end maintance process: Luvina-company
            }
            //#680 end maintance process: Luvina-company
             
            $rai = $conditionMap[_PUBLIC_SEARCH_CONDITION_RAIL];
            if(is_array($rai) && count($rai)) {
                $aryCondition[] = 'cmp_dep_rai_station.sys_rai_id IN (' . join(', ', array_fill(0, count($rai), '?')) . ')';
                $aryParams = array_merge($aryParams, array_values($rai));
            }
        }

        $newDays = $conditionMap[_PUBLIC_SEARCH_CONDITION_NEW_DAYS];
        if($newDays !== false) {
            $date = date("Y-m-d", strtotime("-$newDays day"));
            $aryCondition[] = 'adv_master.adv_date_started >= ?';
            $aryParams = array_merge($aryParams, array($date));
            //#720 start maintance process: Luvina-company
            if ($mgzMailFlg) {
            	$aryCondition[] = 'adv_department.adv_dep_new_status = 1';
            }
            //#720 end maintance process: Luvina-company
        }

        $srvIds = $conditionMap[_PUBLIC_SEARCH_CONDITION_SERVICE];
        if(is_array($srvIds) && count($srvIds)) {
            $aryCondition[] = 'adv_master.adv_service IN (' . join(', ', array_fill(0, count($srvIds), '?')) . ')';
            $aryParams = array_merge($aryParams, array_values($srvIds));
        }

        $words = $conditionMap[_PUBLIC_SEARCH_CONDITION_WORDS];
        if(is_array($words) && count($words) > 0) {
            foreach($words as $word) {
                $word = "%{$word}%";
                $like = "";
                
                $like .= "(cmp_master.cmp_name LIKE ? 
                            OR cmp_department.cmp_dep_name LIKE ? 
                            OR cmp_department.cmp_dep_city IN (SELECT sys_are_city_id FROM sys_are_city WHERE sys_are_city_name LIKE ?)
                            OR cmp_department.cmp_dep_pref IN (SELECT sys_pref_id FROM sys_prefecture WHERE sys_pref_namefull LIKE ?)
                            OR adv_master.adv_top_position LIKE ?
                            OR adv_master.adv_emp_type LIKE ?
                            OR adv_master.adv_service IN (SELECT sys_srv_id FROM sys_srv_master WHERE sys_srv_name LIKE ?)";
                //#698 Start maintance process: Luvina-company #comment:35
                $like .= " OR cmp_department.cmp_dep_id IN (SELECT cmp_dep_rai_station.cmp_dep_id FROM sys_rai_station 
                                    INNER JOIN cmp_dep_rai_station ON cmp_dep_rai_station.sys_rai_sta_id = sys_rai_station.sys_rai_sta_id
                                    WHERE sys_rai_station.sys_rai_sta_name LIKE ?)";
                $like .= " OR cmp_department.cmp_dep_id IN (SELECT cmp_dep_rai_station.cmp_dep_id FROM sys_railroad
                                    INNER JOIN cmp_dep_rai_station ON cmp_dep_rai_station.sys_rai_id = sys_railroad.sys_rai_id
                                    WHERE sys_railroad.sys_rai_name LIKE ?)";
                //#698 End maintance process: Luvina-company #comment:35
                $like .= " OR cmp_department.cmp_dep_treatment LIKE ?
                            OR (
                                (cmp_department.cmp_dep_treatment = ? OR cmp_department.cmp_dep_treatment IS NULL) 
                                AND adv_master.adv_emp_treatment LIKE ?)
                            )";
                $aryCondition[] = $like;
                
                $aryParams[] = $word;
                $aryParams[] = $word;
                $aryParams[] = $word;
                $aryParams[] = $word;
                $aryParams[] = $word;
                $aryParams[] = $word;
                $aryParams[] = $word;
		//#698 Start maintance process: Luvina-company #comment:35
                $aryParams[] = $word;
                $aryParams[] = $word;
		//#698 End maintance process: Luvina-company #comment:35
                $aryParams[] = $word;
                $aryParams[] = '';
                $aryParams[] = $word;
            }
        }

        $advMasterDao = new AdvMasterDao($this->db, $this->backend);
        $sql_sal = "";
        // 給与（年収）
        if (array_key_exists(_PUBLIC_SEARCH_CONDITION_SALARY1, $conditionMap)){
            $salary1 = $conditionMap[_PUBLIC_SEARCH_CONDITION_SALARY1];
            if (is_array($salary1)) {
                if (strlen($sql_sal)>0) {
                    $sql_sal .= "\n or \n";
                }
                $sql_sal .= $advMasterDao->_makeSearchSalaryQuery(1,$salary1["sys_salary_high"],$salary1["sys_salary_low"]);
            }
        }
        // 給与（月給）
        if (array_key_exists(_PUBLIC_SEARCH_CONDITION_SALARY2, $conditionMap)){
            $salary2 = $conditionMap[_PUBLIC_SEARCH_CONDITION_SALARY2];
            if (is_array($salary2)){
                if (strlen($sql_sal)>0) {
                    $sql_sal .= "\n or \n";
                }
                $sql_sal .= $advMasterDao->_makeSearchSalaryQuery(2,$salary2["sys_salary_high"],$salary2["sys_salary_low"]);
            }
        }
        // 給与（時給）
        if (array_key_exists(_PUBLIC_SEARCH_CONDITION_SALARY3, $conditionMap)){
            $salary3 = $conditionMap[_PUBLIC_SEARCH_CONDITION_SALARY3];
            if (is_array($salary3)){
                if (strlen($sql_sal)>0) {
                    $sql_sal .= "\n or \n";
                }
                $sql_sal .= $advMasterDao->_makeSearchSalaryQuery(3,$salary3["sys_salary_high"],$salary3["sys_salary_low"]);
            }
        }
        // 給与（日給）
        if (array_key_exists(_PUBLIC_SEARCH_CONDITION_SALARY4, $conditionMap)){
            $salary4 = $conditionMap[_PUBLIC_SEARCH_CONDITION_SALARY4];
            if (is_array($salary4)){
                if (strlen($sql_sal)>0) {
                    $sql_sal .= "\n or \n";
                }
                $sql_sal .= $advMasterDao->_makeSearchSalaryQuery(4,$salary4["sys_salary_high"],$salary4["sys_salary_low"]);
            }
        }
        // 給与（単価）
        if (array_key_exists(_PUBLIC_SEARCH_CONDITION_SALARY5, $conditionMap)){
            $salary5 = $conditionMap[_PUBLIC_SEARCH_CONDITION_SALARY5];
            if (is_array($salary5)){
                if (strlen($sql_sal)>0) {
                    $sql_sal .= "\n or \n";
                }
                $sql_sal .= $advMasterDao->_makeSearchSalaryQuery(5,$salary5["sys_salary_high"],$salary5["sys_salary_low"]);
            }
        }
        if (strlen($sql_sal)>0) $aryCondition[] = " \n(".$sql_sal."\n ) \n";

        /*
         * 特集求人用会社ID
         */
        $cmps = $conditionMap[_FEATURE_SEARCH_CONDITION_COMPANY];
        if(is_array($cmps) && count($cmps) > 0) {
            $aryCondition[] = 'adv_master.cmp_id IN (' . join(', ', array_fill(0, count($cmps), '?')) . ')';
            $aryParams = array_merge($aryParams, array_values($cmps));
        }
        
        $feaSrvIds = $conditionMap[_FEATURE_SEARCH_CONDITION_SERVICE];
        if(is_array($feaSrvIds) && count($feaSrvIds) > 0) {
            $aryCondition[] = 'adv_master.adv_service IN (' . join(', ', array_fill(0, count($feaSrvIds), '?')) . ')';
            $aryParams = array_merge($aryParams, array_values($feaSrvIds));
        }

        $now  = date("Y-m-d H:i:s");
        $aryCondition[] = 'adv_master.adv_status=?';
        $aryParams[] = 0;
        $aryCondition[] = 'adv_master.adv_date_started < ?';
        $aryParams[] = $now;
        $aryCondition[] = 'DATE_ADD(adv_master.adv_date_expired,INTERVAL 1 DAY) > ?';
        $aryParams[] = $now;

        // #801【機能追加要求】プレミア広告用募集職種一覧からの検索結果に事業所【その他】を表示
        if ($conditionMap[_PUBLIC_SEARCH_CONDITION_OTHER_DEP] !== true) {
            $aryCondition[] = 'cmp_department.cmp_dep_city NOT IN (?)';
            $aryParams[] = '99999';
        }
        
        $advDepIds = isset($conditionMap[_PUBLIC_SEARCH_CONDITION_NOT_IN_ADV_DEP_IDS])?$conditionMap[_PUBLIC_SEARCH_CONDITION_NOT_IN_ADV_DEP_IDS]:null;
        if(is_array($advDepIds) && count($advDepIds) > 0) {
            $aryCondition[] = 'adv_department.adv_dep_id NOT IN (' . join(', ', array_fill(0, count($advDepIds), '?')) . ')';
            $aryParams = array_merge($aryParams, array_values($advDepIds));
        }
        
        // mem_apply
        $memId = isset($conditionMap[_PUBLIC_SEARCH_CONDITION_MEM_ID])?$conditionMap[_PUBLIC_SEARCH_CONDITION_MEM_ID]:null;
        if($pattern == _PUBLIC_SEARCH_LOGINED_PATTERN_HISTORY_APPLY && $memId > 0) {
            $aryCondition[] = 'mem_apply.mem_id = ?';
            $aryParams[] = $memId;
        }
        
        $memApplyDay = isset($conditionMap[_PUBLIC_SEARCH_CONDITION_MEM_APPLY_DAY])?$conditionMap[_PUBLIC_SEARCH_CONDITION_MEM_APPLY_DAY]:null;
        if($pattern == _PUBLIC_SEARCH_LOGINED_PATTERN_HISTORY_APPLY && $memApplyDay > 0) {
            $aryCondition[] = 'DATE_ADD(mem_apply.mem_app_apl_time,INTERVAL '. (int)$memApplyDay .' DAY) > ?';
            $aryParams[] = $now;
        }
        //#688 start maintance process: Luvina-company
        $cmpids = isset($conditionMap[_PUBLIC_SEARCH_CONDITION_NOT_IN_CMP_IDS])?$conditionMap[_PUBLIC_SEARCH_CONDITION_NOT_IN_CMP_IDS]:null;
        if(is_array($cmpids) && count($cmpids) > 0) {
            $aryCondition[] = 'cmp_department.cmp_id NOT IN (' . join(', ', array_fill(0, count($cmpids), '?')) . ')';
            $aryParams = array_merge($aryParams, array_values($cmpids));
        } 
        //#688 end maintance process: Luvina-company 
        //#876 start maintance process: Luvina-company
        $cmpOmitFlg = isset($conditionMap[_PUBLIC_SEARCH_CONDITION_CMP_OMIT])? $conditionMap[_PUBLIC_SEARCH_CONDITION_CMP_OMIT]: null;
        if(!is_null($cmpOmitFlg) && is_numeric($cmpOmitFlg)) {
            //#876 start maintance process: Luvina-company #comment:23
            $aryCondition[] = 'adv_master.adv_cmp_omit_flg = ?';
            //#876 end maintance process: Luvina-company #comment:23
            $param = array($cmpOmitFlg);
            $aryParams = array_merge($aryParams, $param);
        }
        //#876 end maintance process: Luvina-company 
        //prio
        if($pattern == _PUBLIC_SEARCH_LOGINED_PATTERN_PRIOR) {
        	
        	//#686 Start maintance process: Luvina-company:#comment:17
        	if ($mgzMailFlg) {
        		$aryCondition[] = 'adv_master.adv_priority_mgz IN (?,?)';
        	} else {
        		$aryCondition[] = 'adv_master.adv_priority IN (?,?)';
        	}
            //#686 end maintance process: Luvina-company:#comment:17
            $aryParams[] = 1;
            $aryParams[] = 2;
        }
        
        // ^^^^^ new ^^^^^^^^^
        $pref = $cndUser[_PUBLIC_SEARCH_CONDITION_PREF];
        $cmpDepCities = $cndUser[_PUBLIC_SEARCH_CONDITION_CITY];
        //#895 start maintance process: Luvina-company : #comment 15
        $aryConditionTmp = array();
        $prefNearArea = array();
        if(isset($cndUser[_PUBLIC_SEARCH_CONDITION_PREF_NEAR_AREA])) {
            $prefNearArea = $cndUser[_PUBLIC_SEARCH_CONDITION_PREF_NEAR_AREA];
        } 
        if(is_array($prefNearArea) && count($prefNearArea) > 0) {
            $aryConditionTmp[] = 'cmp_department.cmp_dep_pref IN (' . join(', ', array_fill(0, count($prefNearArea), '?')) . ')';
            $aryParams = array_merge($aryParams, array_values($prefNearArea));
        } else
        //#895 end maintance process: Luvina-company : #comment 15
        if(is_array($pref) && count($pref) > 0) {
            //#895 start maintance process: Luvina-company : #comment 15
            $aryConditionTmp[] = 'cmp_department.cmp_dep_pref IN (' . join(', ', array_fill(0, count($pref), '?')) . ')';
            //#895 end maintance process: Luvina-company : #comment 15
            $aryParams = array_merge($aryParams, array_values($pref));
        }
        
        if(is_array($cmpDepCities) && count($cmpDepCities) > 0) {
            //#895 start maintance process: Luvina-company : #comment 15
            if(is_array($prefNearArea) && count($prefNearArea) > 0) {
                $aryConditionTmp[] = 'OR cmp_department.cmp_dep_city IN (' . join(', ', array_fill(0, count($cmpDepCities), '?')) . ')';
            } else {
                $aryConditionTmp[] = (count($aryConditionTmp)? 'AND ': '') . ' cmp_department.cmp_dep_city IN (' . join(', ', array_fill(0, count($cmpDepCities), '?')) . ')';
            }
            $aryParams = array_merge($aryParams, array_values($cmpDepCities));

            //$aryCondition[] = 'cmp_department.cmp_dep_city IN (' . join(', ', array_fill(0, count($cmpDepCities), '?')) . ')';
            //$aryParams = array_merge($aryParams, array_values($cmpDepCities));
            //#895 end maintance process: Luvina-company : #comment 15
        }
        //#895 start maintance process: Luvina-company : #comment 15
        if(count($aryConditionTmp)) {
            $aryCondition[] = '(' . join(" ", $aryConditionTmp) . ')';
        }
        //#895 end maintance process: Luvina-company : #comment 15
        
        $sysPosIds = $cndUser[_PUBLIC_SEARCH_CONDITION_POSITION];
        if(is_array($sysPosIds) && count($sysPosIds)) {
            $aryCondition[] = 'adv_master.adv_position IN (' . join(', ', array_fill(0, count($sysPosIds), '?')) . ')';
            $aryParams = array_merge($aryParams, array_values($sysPosIds));
        }

        $advForm = $cndUser[_PUBLIC_SEARCH_CONDITION_FORM];
        if(is_array($advForm) && count($advForm)) {
            //#663 start maintance process: Luvina-company
            /*
            $aryCondition[] = 'adv_form.adv_form IN (' . join(', ', array_fill(0, count($advForm), '?')) . ')';
            */
        	$aryCondition[] = 'EXISTS(SELECT 1 FROM adv_form WHERE adv_form.adv_id=adv_master.adv_id AND adv_form.adv_form IN(' . join(', ', array_fill(0, count($advForm), '?')) . '))';
            //#663 start maintance process: Luvina-company
            $aryParams = array_merge($aryParams, array_values($advForm));
        }
        
        $feaSrvIds = $cndUser[_FEATURE_SEARCH_CONDITION_SERVICE];
        if(is_array($feaSrvIds) && count($feaSrvIds) > 0) {
            $aryCondition[] = 'adv_master.adv_service IN (' . join(', ', array_fill(0, count($feaSrvIds), '?')) . ')';
            $aryParams = array_merge($aryParams, array_values($feaSrvIds));
        }
        //#884 start maintance process: Luvina-company
        $advNotPrice = isset($cndUser[_PUBLIC_SEARCH_CONDITION_NOT_ADV_PRICE]) ? $cndUser[_PUBLIC_SEARCH_CONDITION_NOT_ADV_PRICE]: null;
        //#890 start maintance process: Luvina-company
        if($advNotPrice == false || is_null($advNotPrice)) {
            $advNotPrice = isset($conditionMap[_PUBLIC_SEARCH_CONDITION_NOT_ADV_PRICE]) ? $conditionMap[_PUBLIC_SEARCH_CONDITION_NOT_ADV_PRICE]: null;
        }
        //#890 end maintance process: Luvina-company 
        if(!is_null($advNotPrice) && is_numeric($advNotPrice)) {
            $aryCondition[] = '(adv_nominal_price.adv_nml_price <> ? OR adv_nominal_price.adv_nml_price IS NULL)';
            $param = array($advNotPrice);
            $aryParams = array_merge($aryParams, $param);
        }
        //#884 end maintance process: Luvina-company 
        //#890 start maintance process: Luvina-company #comment:5
        $advEqualPrice = isset($conditionMap[_PUBLIC_SEARCH_CONDITION_EQUAL_ADV_PRICE]) ? $conditionMap[_PUBLIC_SEARCH_CONDITION_EQUAL_ADV_PRICE]: null;
        if(!is_null($advEqualPrice) && is_numeric($advEqualPrice)) {
            $aryCondition[] = 'adv_nominal_price.adv_nml_price = ?';
            $param = array($advEqualPrice);
            $aryParams = array_merge($aryParams, $param);
        }
        //#890 end maintance process: Luvina-company  #comment:5
    }
    
    /**
     * 
     * Make order by in script sql for each pattern
     * @param Array &aryOrderBy: array ORDER BY
     * @param Int $pattern = _PUBLIC_SEARCH_LOGINED_PATTERN_1,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_2,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_3,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_4,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_5,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_6,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER
     * @param Array $cndFromInput: array conditon search get from $form
     * @param Array $cndUser: array condition search of user get from DB
     * 
     */
    //#663 Start maintance process: Luvina-company:
    public function makeOrderByProjectSearchLogined(&$aryOrderBy, $pattern, $cndFromInput = array(), $cndUser = array(), $mgzMailFlg = false) {
    //#663 End maintance process: Luvina-company:
        switch ($pattern) {
            case _PUBLIC_SEARCH_LOGINED_PATTERN_PRIOR:
            case _PUBLIC_SEARCH_LOGINED_PATTERN_HISTORY_APPLY:
            case _PUBLIC_SEARCH_LOGINED_PATTERN_COUNT:
                break;
            case _PUBLIC_SEARCH_LOGINED_PATTERN_1:
            case _PUBLIC_SEARCH_LOGINED_PATTERN_2:
            case _PUBLIC_SEARCH_LOGINED_PATTERN_3:
            case _PUBLIC_SEARCH_LOGINED_PATTERN_4:
            case _PUBLIC_SEARCH_LOGINED_PATTERN_5:
            case _PUBLIC_SEARCH_LOGINED_PATTERN_6:
            case _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER:
            default:
            	//#663 Start maintance process: Luvina-company:
            	if ($mgzMailFlg) {
            		$aryOrderBy[] = 'adv_department.adv_dep_rand ASC';
            	} else {
	                $aryOrderBy[] = 'adv_department.adv_dep_grp_id ASC';
	                $aryOrderBy[] = 'adv_department.adv_dep_rand ASC';
            	}
            	//#663 End maintance process: Luvina-company:
                break;
        }
    }
    
    /**
     * 
     * Make HAVING in script sql for each pattern
     * @param Array &$aryHaving: array conditon HAVING
     * @param Int $pattern = _PUBLIC_SEARCH_LOGINED_PATTERN_1,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_2,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_3,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_4,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_5,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_6,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER
     * @param Array $cndFromInput: array conditon search get from $form
     * @param Array $cndUser: array condition search of user get from DB
     * 
     */
    public function makeHavingProjectSearchLogined(&$aryHaving, $pattern, $cndFromInput = array(), $cndUser = array()) {
        switch ($pattern) {
            case _PUBLIC_SEARCH_LOGINED_PATTERN_PRIOR:
                $aryHaving[] = 'priority_flg=1';
                break;
        }
    }
    
    /**
     * 
     * Create script SQL for each pattern
     * @param Array $aryParams: array params binding
     * @param Int $pattern = _PUBLIC_SEARCH_LOGINED_PATTERN_1,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_2,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_3,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_4,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_5,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_6,
                        _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER
     * @param Array $cndFromInput: array conditon search get from $form
     * @param Array $cndUser: array condition search of user get from DB
     * 
     * @return String (script SQL)
     */
    //#663 Start maintance process: Luvina-company:
    public function makeSqlProjectSearchLogined(&$aryParams, $pattern, $cndFromInput = array(), $cndUser = array(), $mgzMailFlg = false) {
    	
    	if (!is_array($cndUser) || count($cndUser) == 0) {
    		$this->checkWarningConditon($cndUser);
    	}
    //#663 End maintance process: Luvina-company:
        $aryColumns = $aryFroms = $aryOrderBy = $aryHaving = array();
        
        //#686 Start maintance process: Luvina-company:#comment:17 
        $this->makeSelectProjectSearchLogined($aryColumns, $pattern, $cndFromInput, $cndUser, $mgzMailFlg);
        //#686 end maintance process: Luvina-company:#comment:17 
        $this->makeFromsProjectSearchLogined($aryFroms, $pattern, $cndFromInput, $cndUser);
        //#686 Start maintance process: Luvina-company:#comment:17
        $this->makeWhereProjectSearchLogined($aryParams, $aryCondition, $pattern, $cndFromInput, $cndUser, $mgzMailFlg);
        //#686 end maintance process: Luvina-company:#comment:17
        //#663 Start maintance process: Luvina-company:
        $this->makeOrderByProjectSearchLogined($aryOrderBy, $pattern, $cndFromInput, $cndUser, $mgzMailFlg);
        //#663 End maintance process: Luvina-company:
        $this->makeHavingProjectSearchLogined($aryHaving, $pattern, $cndFromInput, $cndUser);
        
        $sql = 'SELECT ' . join(', ', $aryColumns) .' FROM ' . join(' ', $aryFroms) . ' ';
        if(count($aryCondition)) {
            $sql .= 'WHERE ' . join(' AND ', $aryCondition) . ' ';
        }
        
        if (count($aryHaving) > 0) {
            $sql .= 'HAVING ' . join(' AND ', $aryHaving) . ' ';
        }
        
        if(count($aryOrderBy)) {
            $sql .= 'ORDER BY ' . join(', ', $aryOrderBy) . ' ';
        }
        
        return $sql;
    }
    
    //#636 Start maintance process: Luvina-company: 【Bug ID: 1744】 #comment:36 
    public function search($advIds = array(), $advDepIds = array(), $priorAdvIds = array(), 
     $orderType = 0, $orderTypeSalary = 0, $flg = false, $checkRegistMem = false, $aryMPrior = array()) {
     	$dao = new AdvMasterDao($this->db, $this->backend);
        $ret = $dao->findBySearchConditionForLogined($result, $advIds, $orderType, $orderTypeSalary);
        if($ret == $this->config->config["_DB_ERROR"]) {
        	if($checkRegistMem) {
        		return $ret;
        	}
            return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
        } else {
        /* @var $mgr Kj_PublicSearchManager */
        $mgr = $this->backend->getManager("PublicSearch");
	    $mgr->getResult($result, $priorAdvIds, $flg, $aryMPrior); 
            
            $aryAdvId = array();
            $aryCmpDepId = array();
            foreach($result as $i => $id) {
                $aryAdvId[$id["adv_id"]] = $id["adv_id"];
                $aryCmpDepId[$id['cmp_dep_id']] = $id['cmp_dep_id'];
            }
            $aryAdvId = array_values($aryAdvId);
            $aryCmpDepId = array_values($aryCmpDepId);
            $mgr->getRaiStation($result, $aryCmpDepId);
            $mgr->getFocus($result, $aryAdvId);
        }
        return $result;
    }
    //#636 End maintance process: Luvina-company: 【Bug ID: 1744】 #comment:36 
    //#636 End maintance process: Luvina-company
    
    //#663 start maintenance process: Luvina-company
    public function checkWarningConditon(&$aryCondition) {
    
    	$aryCheckWarning = array(
    			_PUBLIC_SEARCH_CONDITION_POSITION,
    			_PUBLIC_SEARCH_CONDITION_FORM,
    			_PUBLIC_SEARCH_CONDITION_FOCUS,
    			_PUBLIC_SEARCH_CONDITION_STATION,
    			_PUBLIC_SEARCH_CONDITION_CITY,
    			_PUBLIC_SEARCH_CONDITION_PREF,
    			_PUBLIC_SEARCH_CONDITION_STATION,
    			_PUBLIC_SEARCH_CONDITION_RAIL,
    			_PUBLIC_SEARCH_CONDITION_STATION_FROM,
    			_PUBLIC_SEARCH_CONDITION_STATION_TO,
    			_PUBLIC_SEARCH_CONDITION_NEW_DAYS,
    			_PUBLIC_SEARCH_CONDITION_SERVICE,
    			_PUBLIC_SEARCH_CONDITION_WORDS,
    			_PUBLIC_SEARCH_CONDITION_OTHER_DEP,
    			_FEATURE_SEARCH_CONDITION_COMPANY,
    			_FEATURE_SEARCH_CONDITION_DSTR,
    			_FEATURE_SEARCH_CONDITION_SERVICE,
    			_PUBLIC_SEARCH_CONDITION_SALARY1,
    			_PUBLIC_SEARCH_CONDITION_SALARY2,
    			_PUBLIC_SEARCH_CONDITION_SALARY3,
    			_PUBLIC_SEARCH_CONDITION_SALARY4,
    			_PUBLIC_SEARCH_CONDITION_SALARY5,
    			_PUBLIC_SEARCH_CONDITION_NOT_IN_ADV_DEP_IDS,
    			_PUBLIC_SEARCH_CONDITION_MEM_ID,
    			_PUBLIC_SEARCH_CONDITION_MEM_APPLY_DAY,
    			//#688 start maintance process: Luvina-company 
    			_PUBLIC_SEARCH_CONDITION_NOT_IN_CMP_IDS,
    			//#688 end maintance process: Luvina-company
    			//#680 start maintance process: Luvina-company 
    			_PUBLIC_SEARCH_CONDITION_RAISTATION,
    			//#680 end maintance process: Luvina-company
    			//#884 start maintance process: Luvina-company
    			_PUBLIC_SEARCH_CONDITION_CMP_OMIT,
    			_PUBLIC_SEARCH_CONDITION_NOT_ADV_PRICE,
    			//#884 end maintance process: Luvina-company
    			//#890 start maintance process: Luvina-company #comment:5
    			_PUBLIC_SEARCH_CONDITION_EQUAL_ADV_PRICE,
    			//#890 end maintance process: Luvina-company #comment:5
    			//#895 start maintance process: Luvina-company : #comment 15
    			_PUBLIC_SEARCH_CONDITION_PREF_NEAR_AREA,
    			//#895 end maintance process: Luvina-company : #comment 15
    	);
    
    	foreach ($aryCheckWarning as $key) {
    		if (strlen($key) > 0) {
    			$aryCondition[$key] = isset($aryCondition[$key]) ? $aryCondition[$key] : false;
    		}
    	}
    }
    //#663 end maintenance process: Luvina-company
    //#673 start maintance process: Luvina-company
    public function getOccupationByPosition(&$aryOcp, $aryPosition) {
        $dao = new Dao(null, "sys_position", $this->backend);
        $cnd = array(array("sys_pos_id", "IN", array_values($aryPosition)));
        $ret = $dao->getList($aryOcp, $cnd, false, false, array('sys_pos_occupation'));
        if($ret == $this->config->config["_DB_ERROR"]) {
            $this->logger->log(LOG_EMERG, print_r($sysPosition->getMessage(), true));
            return Ethna::raiseError("DBエラーです", E_DB_ERROR);
        }
    }
    //#673 end maintance process: Luvina-company
    
    //#679 start maintance process: Luvina-company
    //#787 start maintance process: Luvina-company
    //#787 start maintance process: Luvina-company : #comment:22 
    public function getSearchListForApply($isApplyDataNew = false,
                                            $aryAdvDepIdRemove = array()) {
    //#787 end maintance process: Luvina-company : #comment:22 
    //#787 start maintance process: Luvina-company
    	
    	$aryPref = $aryCity = $aryPosition = $aryService = $advDepIdExclude = array();
    	$aryAdvId = $aryDepId = array();
    	//#711 start maintance process: Luvina-company
    	/*
    	$aryAdvDepId = $this->af->get('project_id');
    	if (!is_array($aryAdvDepId) || count($aryAdvDepId) == 0) {
    		$aryAdvDepId = $this->session->get('ADV_DEP_ID');
    	}
    	*/
    	$aryAdvDepId = $this->af->get('adv_dep_id');
    	//#711 end maintance process: Luvina-company
    	$isBool = $this->getConditionFromProjectApply($aryAdvDepId, $aryPref, $aryCity, $aryPosition, $aryService, $aryAdvId, $aryDepId);
    	if (!$isBool) {
    		return false;
    	}
    	
    	$aryCondSearch = array();
    	$aryCondSearch[_PUBLIC_SEARCH_CONDITION_PREF] = $aryPref;
    	
    	$aryUserInfo = array();
    	$aryUserInfo['city'] = $aryCity;
    	$aryUserInfo['srv_id'] = $aryService;
    	$aryUserInfo['pos'] = $aryPosition;
    	
    	//Get form
    	$memId = $this->session->get('MEM_ID');
    	$memApplyDao = new MemApplyDao($this->db, $this->backend, null);
    	$memApplyCond = array(
    			array('mem_id', '=', $memId),
    			array('mem_app_adv_id', 'IN', $aryAdvId),
    			array('mem_app_dep_id', 'IN', $aryDepId)
    	);
    	
    	$memApply = $columns = array();
    	$columns[] = ' DISTINCT mem_app_frm_id';
    	$retval = $memApplyDao->getList($memApply, $memApplyCond, false, false, $columns);
    	if($retval == $this->config->config['_DB_ERROR']) {
    		return Ethna::raiseError("DBエラーです", E_DB_ERROR);
    	}
    	
    	$aryForm = array();
    	foreach ($memApply as $value) {
    		$aryForm[] = $value['mem_app_frm_id'];
    	}
    	if (count($aryForm) == 1) {
    		$aryUserInfo['frm_id'] = $aryForm;
    	}
    	
    	//Set condition for form search
    	$this->af->set('pref', $aryPref);
    	$this->af->set('re-search',1);
    	//#787 start maintance process: Luvina-company
        //#787 start maintance process: Luvina-company : #comment:22 
        $this->getSearchListForRegistMemAndApply($aryCondSearch,
                                                    $isNoGetMemInfoDB = true,
                                                    $aryUserInfo,
                                                    true,
                                                    $isApplyDataNew,
                                                    $aryAdvDepIdRemove
                                                    );
        //#787 end maintance process: Luvina-company : #comment:22
    	//#787 end maintance process: Luvina-company
    }

    //#787 start maintance process: Luvina-company : #comment:22
    //#815 start maintance process: Luvina-company
    //#861 start maintenance process: Luvina-company
    public function getSearchListForRegistMem($isApplyDataNew = false, $limit = null, $aryMemSkill) {
    //#861 end maintenance process: Luvina-company
    //#815 end maintance process: Luvina-company
    //#787 end maintance process: Luvina-company : #comment:22
    	
    	$dataUser = $this->getMemInfoForSearchLogined();
    	if ($dataUser === $this->config->config['_DB_ERROR']) {
    		return Ethna::raiseError("DBエラーです", E_DB_ERROR);
    	}
    	
    	//#679 start maintance process: Luvina-company: #comment:27
    	/* 
    	$aryCityNear = array();
    	if(is_array($dataUser['city']) && count($dataUser['city'])) {
    		$aryCityNear = $this->getNearAreaByArrayCityIs($dataUser['city'], false);
    		if ($aryCityNear === $this->config->config['_DB_ERROR']) {
    			return Ethna::raiseError("DBエラーです", E_DB_ERROR);
    		}
    	}
    	 */
    	$aryPref = array_values($dataUser['pref']);
    	$aryCondSearch = array();
    	$aryCondSearch[_PUBLIC_SEARCH_CONDITION_PREF] = $aryPref;
        //#861 start maintenance process: Luvina-company 
        if(count($aryMemSkill) == 1 && $aryMemSkill[0] == 12) {
           $aryCondSearch[_PUBLIC_SEARCH_CONDITION_FOCUS] = array(2);
        }
        //#861 end maintenance process: Luvina-company 
    	/* 
    	$aryCondSearch[_PUBLIC_SEARCH_CONDITION_CITY] = array_values(array_merge($dataUser['city'], $aryCityNear));
    	$aryCondSearch[_PUBLIC_SEARCH_CONDITION_POSITION] = array_values($dataUser['pos']);
    	
    	//Set condition for form search
    	$aryPref = array_values($dataUser['pref']);
    	if (count($aryCityNear) > 0) {
    		foreach ($aryCityNear as $cityNear) {
    			$prefId = (int)substr($cityNear, 0, 2);
    			if (!in_array($prefId, $aryPref)) {
    				$aryPref[] = $prefId;
    			}
    		}
    	}
    	 */
    	$this->af->set('pref', $aryPref);
    	/* 
    	$this->af->set('city', $aryCondSearch[_PUBLIC_SEARCH_CONDITION_CITY]);
    	
    	//get occupation
    	$sysPositionDao = new SysPositionDao($this->db, $this->backend);
    	
    	$aryCond = array(
    			array('sys_pos_id', 'IN', array_values($dataUser['pos']))
    	);
    	
    	$columns[] = ' DISTINCT sys_pos_occupation';
    	
    	$aryOccupation = array();
    	$retval = $sysPositionDao->getList($aryOccupation, $aryCond, false, false, $columns);
    	if($retval == $this->config->config['_DB_ERROR']) {
    		return Ethna::raiseError("DBエラーです", E_DB_ERROR);
    	}
    	
    	$aryOcpId = array();
    	foreach ($aryOccupation as $value) {
    		$aryOcpId[] = $value['sys_pos_occupation'];
    	}
    	$this->af->set('occupation', $aryOcpId);
    	 */
    	//#787 start maintance process: Luvina-company : #comment:22
        //#815 start maintance process: Luvina-company
    	$this->getSearchListForRegistMemAndApply($aryCondSearch, false, array(), true, $isApplyDataNew, array(), $limit);
        //#815 end maintance process: Luvina-company
    	//#787 end maintance process: Luvina-company : #comment:22
    	//#679 start maintance process: Luvina-company: #comment:27
    }
    //#787 start maintance process: Luvina-company 
    //#787 start maintance process: Luvina-company : #comment:22 
    //#815 start maintance process: Luvina-company
    public function getSearchListForRegistMemAndApply($aryCondSearch,
                                                        $isNoGetMemInfoDB = false,
                                                        $aryUserInfo = array(),
                                                        $havePatternOther = true,
                                                        $isApplyDataNew = false,
                                                        $aryAdvDepIdRemove = array(),
                                                        $limit = null
                                                        ) {
    //#815 end maintance process: Luvina-company
    //#787 start maintance process: Luvina-company : #comment:22 
    //#787 end maintance process: Luvina-company
    	$this->checkWarningConditon($aryCondSearch);
    	

    	/* @var $mgrPublicSearch Kj_PublicSearchManager */
    	$mgrPublicSearch = $this->backend->getManager("PublicSearch");
    	
    	// ページ情報
    	$cPage = $mgrPublicSearch->getPageNo();
    	
    	/* @var $mgrPublic Kj_PublicManager */
    	$mgrPublic = $this->backend->getManager("Public");
    	//#787 start maintance process: Luvina-company : #comment:22 
    	//#815 start maintance process: Luvina-company
    	if (!is_numeric($limit) || $limit < 1) {
    	    $limit = $mgrPublic->getOutputRecord($isApplyDataNew);
    	}
    	//#815 end maintance process: Luvina-company
    	//#787 end maintance process: Luvina-company : #comment:22 
    	$ot = $this->af->get('ot');
    	$ots = $this->af->get('ots');
    	$total = 0;
    	
    	$result = $this->getProjectSearchLogined(
                        $aryPrior, 
                        $aryMPrior, 
                        $total, 
                        $limit, 
                        $aryCondSearch, 
                        $ot, 
                        $ots, 
                        $cPage, 
                        $isPrioProcess = false,
                        $isPrioRandomFlg = false,
                        $isNoGetMemInfoDB,
                        $aryUserInfo,
                        $havePatternOther
                        //#787 start maintance process: Luvina-company : #comment:22 
                        , $aryAdvDepIdRemove
                        //#787 end maintance process: Luvina-company : #comment:22 
    	);
        //#787 start maintance process: Luvina-company
        if ($isApplyDataNew) {
            $this->getDataProjectDetailForApply($result);
        }
        //#787 end maintance process: Luvina-company
	
    	$this->af->setAppNE(_CONSUMER_MYSEARCH_SEARCH_RESULT, $result);
    	
    	//paging
    	$this->updatePagingForRegistMemAndApply($page, $total, $limit);
    }
    
    private function updatePagingForRegistMemAndApply(&$page, $total, $limit) {

    	if(!$this->session->isStart()) {
    		$this->session->start();
    	}
    	
    	/* @var $mgrPublicSearch Kj_PublicSearchManager */
    	$mgrPublicSearch = $this->backend->getManager("PublicSearch");
    	 
    	/* @var $mgrPublic Kj_PublicManager */
    	$mgrPublic = $this->backend->getManager("Public");
    	
    	// ページ情報
    	$toPage = $mgrPublicSearch->getPageNo();
    	
    	$page = $this->session->get(_CONSUMER_MYSEARCH_SEARCH_PAGE);
    	$prePage = $page['currPage'];
    	 
    	$mgrPublic->movePageTo($page, $total, $toPage, $limit);
    	if(Net_UserAgent_Mobile::isSmartPhone()) {
    		$aryPageSmartPhone = $mgrPublicSearch->pagingSmartPhone($page['currPage'], $page['endPage'], $prePage);
    		$page['pageSmartPhone'] = $aryPageSmartPhone;
    	}
    	 
    	$this->session->set(_CONSUMER_MYSEARCH_SEARCH_PAGE, $page);
    	 
    	$this->af->set('page', $toPage);
    	
    }
    //#695 start maintance process: Luvina-company
    public function getConditionFromProjectApply($aryAdvDepId, &$aryPref, &$aryCity, &$aryPosition, &$aryService, &$aryAdvId, &$aryDepId) {
    //#695 end maintance process: Luvina-company
    	if (!is_array($aryAdvDepId) || count($aryAdvDepId) == 0) {
    		return false;
    	}
    	 
    	$aryAdvId = $aryDepId = array();
    	foreach ($aryAdvDepId as $value) {
    		$aryTmp = explode(',', $value);
    		$aryAdvId[$aryTmp[0]] = (int)$aryTmp[0];
    		$aryDepId[$aryTmp[1]] = (int)$aryTmp[1];
    	}
    	
    	$aryAdvId = array_values($aryAdvId);
    	$aryDepId = array_values($aryDepId);
    	
    	$params = array();
    	$sql = "SELECT
    	adv_department.adv_id
    	,adv_department.cmp_dep_id
    	,adv_master.adv_position
    	,adv_master.adv_service
    	,cmp_department.cmp_dep_city
    	,cmp_department.cmp_dep_pref
    	FROM adv_department
    	INNER JOIN adv_master ON adv_master.adv_id = adv_department.adv_id
    	INNER JOIN cmp_department ON cmp_department.cmp_dep_id = adv_department.cmp_dep_id
    	WHERE
    	cmp_department.cmp_dep_city NOT IN ('99999')
    	";
    	
    	if (count($aryAdvId) > 0) {
    		$sql .= " AND adv_department.adv_id IN (" . join(',', array_fill(0, count($aryAdvId), '?')) . ")";
    		$params = array_merge($params, $aryAdvId);
    	}

    	if (count($aryDepId) > 0) {
    		$sql .= " AND adv_department.cmp_dep_id IN (" . join(',', array_fill(0, count($aryDepId), '?')) . ")";
    		$params = array_merge($params, $aryDepId);
    	}
    	
    	$result = $this->db->db->getAll($sql, $params, DB_FETCHMODE_ASSOC);
    	if (Ethna::isError($result)) {
	    	$this->logger->log(LOG_EMERG, print_r($result->getMessage(), true));
	    	return Ethna::raiseError("DBエラーです", E_DB_ERROR);
    	}
    	
    	$aryAdvTempId = $aryDepTempId = array();
    	foreach ($result as $value) {
    		
    		$aryAdvTempId[$value['adv_id']] = $value['adv_id'];
    		$aryDepTempId[$value['cmp_dep_id']] = $value['cmp_dep_id'];
    		$aryPref[$value['cmp_dep_pref']] = $value['cmp_dep_pref'];
    		$aryCity[$value['cmp_dep_city']] = $value['cmp_dep_city'];
    		$aryPosition[$value['adv_position']] = $value['adv_position'];
    		$aryService[$value['adv_service']] = $value['adv_service'];
    	}
    	
    	$aryAdvId = array_values($aryAdvTempId);
    	$aryDepId = array_values($aryDepTempId);
    	
    	$aryPref = array_values($aryPref);
    	$aryCity = array_values($aryCity);
    	$aryPosition = array_values($aryPosition);
    	$aryService = array_values($aryService);
    	
    	return count($result) > 0;
    }
    //#679 end maintance process: Luvina-company
    //#693 start maintance process: Luvina-company
    public function searchProjectForMyTop(&$total, &$aryConditon, $limit = 5) {
        //get data sort
        $dataUser = $this->getMemInfoForSearchLogined();
        if ($dataUser === $this->config->config['_DB_ERROR']) {
            return $this->config->config['_DB_ERROR'];
        }
        //get data search
        $prefs = $dataUser['pref'];
        $arySort['pref'] = array_values($dataUser['pref']);
        $arySort['city'] = array_values($dataUser['city']);
        if(is_array($dataUser['srv_id']) && count($dataUser['srv_id']) > 0) {
            $arySort['srv_id'] = array_values($dataUser['srv_id']);
        }
        $arySort['pos'] = array_values($dataUser['pos']);
        $arySort['frm_id'] = array_values($dataUser['frm_id']);
        unset($dataUser);
        $aryConditon = $arySort;
        $cndArray = array();
        $cndArray[_PUBLIC_SEARCH_CONDITION_PREF] = array_values($prefs);
        $cndArray[_PUBLIC_SEARCH_CONDITION_CITY] = false;
        $cndArray[_PUBLIC_SEARCH_CONDITION_FORM] = false;
        $cndArray[_PUBLIC_SEARCH_CONDITION_POSITION] = false;
        $cndArray[_PUBLIC_SEARCH_CONDITION_SERVICE] = false;
        return $this->searchProjectByCondtion($cndArray, $arySort, $total, $limit);
    }
    
    public function searchExamDataForMyTop(&$total, &$aryConditon, $limit = 5) {
        //getData Exam
        $memId = $this->session->get('MEM_ID');
        $dao = new MemExamApplyDao($this->db, $this->backend, $memId);
        $result = array();
        $dateExam = $this->config->get('top_search_exam_days');
        if(is_null($dateExam) || !is_numeric($dateExam) || $dateExam < 0) {
            $dateExam = 30;
        }
        $code = $dao->getLastExamApplyByMemId($result, $dateExam);
        if ($code != $this->config->config['_DB_OK']) {
            return $code;
        }
        $aryPref = $aryCity = $aryPosition = $aryService = $aryForm = array();
        $aryPref[$value[0]['cmp_dep_pref']] = $result[0]['cmp_dep_pref'];
        $aryCity[$value[0]['cmp_dep_city']] = $result[0]['cmp_dep_city'];
        $aryPosition[$value[0]['adv_position']] = $result[0]['adv_position'];
        $aryService[$value[0]['adv_service']] = $result[0]['adv_service'];
        $advId = $result[0]['adv_id'];
        
        unset($result);
        //get adv_form
        $advFormDao = new AdvFormDao($this->db, $this->backend);
        $code = $advFormDao->getAdvFormByAdvId($result, $advId);
        if($code != $this->config->config['_DB_OK']) {
            return $code;
        }
        foreach ($result as $value) {
            $aryForm[$value['adv_form']] = $value['adv_form'];
        }
        unset($result);
        
        //make condition sort
        $cndSort = array();
        $cndSort['pref'] = array_values($aryPref);
    	$cndSort['city'] = array_values($aryCity);
    	$cndSort['srv_id'] = array_values($aryService);
    	$cndSort['pos'] = array_values($aryPosition);
        $cndSort['frm_id'] = array_values($aryForm);
        
        $aryConditon = $cndSort;
        //unset data
        unset($aryPref);
        unset($aryCity);
        unset($aryPosition);
        unset($aryService);
        unset($aryForm);
        
        //make condition search
        $cndArray = array();
        $cndArray[_PUBLIC_SEARCH_CONDITION_PREF] = $cndSort['pref'];
        $cndArray[_PUBLIC_SEARCH_CONDITION_CITY] = false;
        $cndArray[_PUBLIC_SEARCH_CONDITION_FORM] = false;
        $cndArray[_PUBLIC_SEARCH_CONDITION_POSITION] = false;
        $cndArray[_PUBLIC_SEARCH_CONDITION_SERVICE] = false;
        
        return $this->searchProjectByCondtion($cndArray, $cndSort, $total, $limit);
    }
    
    public function searchApplyDataForMyTop(&$total, &$aryConditon, $limit = 5) {
        //getData Exam
        $memId = $this->session->get('MEM_ID');
        $dao = new MemApplyDao($this->db, $this->backend);
        $result = array();
        $dateApply = $this->config->get('top_search_app_days');
        if(is_null($dateApply) || !is_numeric($dateApply) || $dateApply < 0) {
            $dateApply = 30;
        }
        $code = $dao->getLastHisotyApplyByMemId($result, $memId, $dateApply);
        if ($code != $this->config->config['_DB_OK']) {
            return $code;
        }
        $aryPref = $aryCity = $aryPosition = $aryService = $aryForm = array();
        $aryPref[$value[0]['cmp_dep_pref']] = $result[0]['cmp_dep_pref'];
        $aryCity[$value[0]['cmp_dep_city']] = $result[0]['cmp_dep_city'];
        $aryPosition[$value[0]['adv_position']] = $result[0]['adv_position'];
        $aryService[$value[0]['adv_service']] = $result[0]['adv_service'];
        $aryForm[$value[0]['mem_app_frm_id']] = $result[0]['mem_app_frm_id'];
        unset($result);
        
        //make condition sort
        $cndSort = array();
        $cndSort['pref'] = array_values($aryPref);
    	$cndSort['city'] = array_values($aryCity);
    	$cndSort['srv_id'] = array_values($aryService);
    	$cndSort['pos'] = array_values($aryPosition);
        $cndSort['frm_id'] = array_values($aryForm);
        
        $aryConditon = $cndSort;
        //unset data
        unset($aryPref);
        unset($aryCity);
        unset($aryPosition);
        unset($aryService);
        unset($aryForm);
        
        //make condition search
        $cndArray = array();
        $cndArray[_PUBLIC_SEARCH_CONDITION_PREF] = $cndSort['pref'];
        $cndArray[_PUBLIC_SEARCH_CONDITION_CITY] = false;
        $cndArray[_PUBLIC_SEARCH_CONDITION_FORM] = false;
        $cndArray[_PUBLIC_SEARCH_CONDITION_POSITION] = false;
        $cndArray[_PUBLIC_SEARCH_CONDITION_SERVICE] = false;
        return $this->searchProjectByCondtion($cndArray, $cndSort, $total, $limit);
    }
    
    public function searchProjectByCondtion(&$cndArray, &$cndSort, &$total, $limit) {
        $aryPrior = $aryMPrior = array();
        $cndArray[_PUBLIC_SEARCH_CONDITION_NEW_DAYS] = false;
        $cndArray[_PUBLIC_SEARCH_CONDITION_FOCUS] = false;
        $total = 0;
        $aryPattern = array(
            _PUBLIC_SEARCH_LOGINED_PATTERN_1,
            _PUBLIC_SEARCH_LOGINED_PATTERN_2,
            _PUBLIC_SEARCH_LOGINED_PATTERN_3,
            _PUBLIC_SEARCH_LOGINED_PATTERN_4,
            _PUBLIC_SEARCH_LOGINED_PATTERN_5,
            _PUBLIC_SEARCH_LOGINED_PATTERN_6,
            _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER,
        );
        
        foreach ($aryPattern as $pattern) {
            $cndUserPattern[$pattern] = $this->getConditionPattern($pattern, null, null,false,
                                                             true, $cndSort);
            if ($cndUserPattern[$pattern] === $this->config->config['_DB_ERROR']) {
                return Ethna::raiseError("DBエラーです。", E_DB_ERROR);
            }
        }
        $adv_dep_ids = $this->getAdvDepIdsSearchLogined(
                                $aryPrior, 
                                $aryMPrior, 
                                $total, 
                                $limit, 
                                $cndArray, 
                                1, 
                                false,
                                false,
                                array(),
                                $cndUserPattern
                        );
        if ($adv_dep_ids === $this->config->config['_DB_ERROR']) {
            return $adv_dep_ids;
        }
        if (!is_array($adv_dep_ids) || count($adv_dep_ids) == 0) {
            return $this->config->config['_DB_EMPTY'];
        }
        return $this->getDataProject($adv_dep_ids);
    }
    
    
    public function getDataProject($aryAdvDepId) {
        $dao = new AdvMasterDao($this->db, $this->backend);
        $result = array();
        $code = $dao->getSimilarAdvsLogined($result, $aryAdvDepId);
        if ($code !== $this->config->config['_DB_OK']) {
                return $code;
        }
        //#697 start maintance process: Luvina-company (【Bug ID: 1979】 #comment:32)
        // move this logic to Consumer/Mypage/Index.php
        /*
        foreach($result as $k => $adv) {
            $img = parent::pageGetImageName(
                $this->config->get('DIR', 'HTML')."img/sys/search/", $adv["adv_id"]);
                $result[$k]["image"] = ($img !== false) ? "/img/sys/search/".$img : '/img/adv/kj_ph_noimg_s.gif';
        }
        */
        //#697 end maintance process: Luvina-company (【Bug ID: 1979】 #comment:32)
        /* @var $publicMgr Kj_PublicManager */
        $publicMgr = $this->backend->getManager('Public');
        $publicMgr->appendToListSimilarAdvs(&$result);
        
        //#697 start maintance process: Luvina-company (【Bug ID: 1979】 #comment:32)
        // move this logic to Consumer/Mypage/Index.php
        //format data
        /*
        foreach ($result as $key => $value) {
            //adv_top_position
            $adv_top_position = $value['adv_top_position'];
            //#697 start maintance process: Luvina-company : #comment:28
            if (mb_strlen($adv_top_position) > 6) {
                $adv_top_position = mb_substr($value['adv_top_position'], 0, 6) . '…';
            //#697 start maintance process: Luvina-company : #comment:28
                $result[$key]['adv_top_position'] = $adv_top_position;
            }
            //salary_1
            $strSalary = $value['salary_1'];
            if (mb_strlen($strSalary) > 12) {
                $strSalary = mb_substr($strSalary, 0, 12) . '…';
                $result[$key]['salary_1'] = $strSalary;
            }
        }
        */
        //#697 end maintance process: Luvina-company (【Bug ID: 1979】 #comment:32)
        return $result;
    }
    //#693 end maintance process: Luvina-company
    //#703 start maintenance process: Luvina-company 
    public function checkErrorInput($form, $error) {
        $isSendMail = false;
        $isActive = $this->config->get('isActive');
        if($isActive) {
            $pattern = $this->config->get('pattern');
            foreach($error->getErrorList() as $obj){
                $errCode = $obj['object']->code;
                if($pattern[_PATTERN_ERR_UNIQUEEMAIL] == 1 && ($obj['name'] == 'form_mem_mail' || $obj['name'] == 'mem_mail') && $errCode == E_FORM_INVALIDVALUE) {
                    $isSendMail = true;
                }
                if($pattern[_PATTERN_ERR_CHECKDATE] == 1 && $form['mem_birthday_month'] && $form['mem_birthday_day'] && $form['mem_birthday_year'] 
                  && !checkdate($form['mem_birthday_month'], $form['mem_birthday_day'], $form['mem_birthday_year'])) {
                    $isSendMail = true;
                }
                //#741 start maintenance process: Luvina-company: 【Bug ID: 2100】 comment:7
                $errMinMax = false;
                if($obj['name'] == 'mem_mobile' || $obj['name'] == 'mem_tel') {
                    $var = ($obj['name'] == 'mem_mobile') ? $form['mem_mobile'] : $form['mem_tel'];
                    $tmp = preg_replace("/\(|\)|-/", "", $var);
                    if (preg_match("/^050|^070|^080|^090/", $tmp)) {
                        $maxlen = $minlen = 11;
                    } else {
                        $maxlen = $minlen = 10;
                    }
                    
                    if(strlen($tmp) > $maxlen || strlen($tmp) < $minlen) {
                        $errMinMax = true;
                    }
                }
                
                if($pattern[_PATTERN_ERR_MIN_MAX] == 1 && ($errMinMax || $errCode == E_FORM_MIN_INT ||
                //#741 end maintenance process: Luvina-company: 【Bug ID: 2100】 comment:7
                  $errCode == E_FORM_MIN_FLOAT || $errCode == E_FORM_MIN_DATETIME || $errCode == E_FORM_MIN_FILE ||
                  $errCode == E_FORM_MIN_STRING || $errCode == E_FORM_MAX_INT || $errCode == E_FORM_MAX_FLOAT || 
                  $errCode == E_FORM_MAX_DATETIME || $errCode == E_FORM_MAX_FILE || $errCode == E_FORM_MAX_STRING)) {
                    $isSendMail = true;
                }
                //#703 start maintenance process: Luvina-company: 【Bug ID: 2024】 #comment:22
                //#741 start maintenance process: Luvina-company
                if($pattern[_PATTERN_ERR_REQUIRED] == 1 && ($errCode == E_FORM_REQUIRED || ($form['mem_mobile'] === '' && $form['mem_tel'] === ''))) {
                //#741 end maintenance process: Luvina-company
                //#703 end maintenance process: Luvina-company: 【Bug ID: 2024】 #comment:22
                    $isSendMail = true;
                }
            }
        }
        return $isSendMail;
    }
    
    //#762 start maintenance process: Luvina-company
    /**
     * send mail to admin
     * @param $form array
     * @param $objAE object
     * @param $aryErrorMess array
     */
    public function sendMailToSms($form, $objAE = null, $aryErrorMess = array()) {
    //#762 end maintenance process: Luvina-company
        $url_error = getenv("HTTP_REFERER");
        $prefDao = new SysPrefectureDao($this->db, $this->backend);
        $cityDao = new SysAreCityDao($this->db, $this->backend);
        $memMasterDao = new MemMasterDao($this->db, $this->backend);
        $skillDao = new SysSkillDao($this->db, $this->backend);
        $macro = $aryError = $contents = array();
        if(strpos($url_error,"consumer_preent")!==false) {
            unset($form['mem_address']);
        }
        
        //#867 start maintenance process: Luvina-company]
        if (strlen($form['mem_mail']) > 0) {
            $form['mem_mail'] = parent::_htmlspecialchars_decode($form['mem_mail']);
        } 
        if (strlen($form['form_mem_mail']) > 0) {
            $form['form_mem_mail'] = parent::_htmlspecialchars_decode($form['form_mem_mail']);
        } 
        //#867 end maintenance process: Luvina-company
        //#762 start maintenance process: Luvina-company
        if(is_object($objAE) && $objAE->count() > 0) {
            foreach ($form as $key => $info) {
                $errorMess = $objAE->getMessage($key);
                if($errorMess) {
                    $aryError[] = $errorMess;
                }
            }
        }
        
        if(is_array($aryErrorMess) && count($aryErrorMess) > 0) {
            $aryError = array_merge($aryError, $aryErrorMess);
        }
        //#762 end maintenance process: Luvina-company
        
        $content_export = $this->config->get('content_export');
        foreach ($content_export as $input) {
            if($input == 'mem_pref_id' && $form['mem_pref_id'] != 0) {
                $prefDao->getPrefName($preName, $form['mem_pref_id']);
                $contents[$input] = $form['mem_pref_id'] . '->' . $preName;
            } elseif($input == 'mem_city_id' && $form['mem_city_id'] != 0) {
                $cityDao->getSysAreCityName($cityName, $form['mem_city_id']);
                $contents[$input] = $form['mem_city_id'] . '->' . $cityName;
            } elseif($input == 'mem_skl_id' && is_array($form['mem_skl_id']) && count($form['mem_skl_id']) > 0) {
                $arySkillName = $arySysSkill = array();
                $arySysSkill = $skillDao->getSysSkill();
                foreach ($form['mem_skl_id'] as $skill) {
                    $arySkillName[] = $skill . '->' . $arySysSkill[$skill];
                }
                $contents[$input] = implode("、", $arySkillName);
            } elseif($input == 'mem_frm_id' && is_array($form['mem_frm_id']) && count($form['mem_frm_id']) > 0) {
                $aryFormName = array();
                foreach ($form['mem_frm_id'] as $frm) {
                    $aryFormName[] = ($frm == 1) ? '1->正社員・契約社員' : '2->パート・その他';
                }
                $contents[$input] = implode("、", $aryFormName);
            } elseif($input == 'mem_career_id' && $form['mem_career_id']) {
                $contents[$input] = ($form['mem_career_id'] == 1) ? '1->現役学生' : (($form['mem_career_id'] == 2) ? '2->3年未満' : '3->3年以上');
            } elseif(isset($form[$input])) {
                $contents[$input] = ($form[$input]) ? $form[$input] : '';
            }
        }
        $macro['date_time'] = date("Y-m-d H:i:s");
        $macro['user_agent'] = $this->setUserAgentForMem(null, false);
        $macro['mail']['error_message'] = $aryError;
        $macro['mail']['content_input'] = $contents;
        $macro['mail']['url_error'] = $url_error;
        $uri = explode("=", $url_error);
        $act = explode("&", $uri[1]);
        $macro['act'] = $act[0];
        $macro['mem_mail'] = ($form['mem_mail']) ? $form['mem_mail'] : $form['form_mem_mail'];
        $macro['mem_tel'] = $form['mem_tel'];
        $macro['mem_mobile'] = $form['mem_mobile'];
        //#703 start maintenance process: Luvina-company: 【Bug ID: 2026】 #comment:24
        if($macro['mem_mail']) {
            $memMasterDao->findMemMUser($aryMem, array(array('mem_mail', '=', $macro['mem_mail'])), false, array('mem_mail','mem_mobile','mem_tel'));
            if($aryMem[0]['mem_mail'] == $macro['mem_mail']) {
                $macro['is_regist']['mail'] = 1;
                
                if($macro['mem_tel'] && $aryMem[0]['mem_tel'] == $macro['mem_tel']) {
                    $macro['is_regist']['tel'] = 1;
                }
                
                if($macro['mem_mobile'] && $aryMem[0]['mem_mobile'] == $macro['mem_mobile']) {
                    $macro['is_regist']['mobile'] = 1;
                }
            }
        }
        //#703 end maintenance process: Luvina-company: 【Bug ID: 2026】 #comment:24
        //#748 start maintenance process: Luvina-company

      //sms update 20131127 Luvina-Bug
      //$advDepId = $form['adv_dep_id'];
        $advDepId = $this->session->get('ADV_DEP_ID');

        $this->logger->log(LOG_INFO,"adv_dep_id". $advDepId);
        if(is_array($advDepId) && count($advDepId) > 0) {
            $aryDetaiAdvDepId = $this->getDetailAdvDepId($advDepId);
            $macro['detail_adv_dep_id'] = $aryDetaiAdvDepId;
        }
        //#748 end maintenance process: Luvina-company
        $address = $this->config->get('address_send_mail');
        $ethna_mail =& new Kj_MailSender($this->backend);
        foreach ($address as $mail) {
            $ethna_mail->send(
                $mail,
                'Manager_Mementry_Error_Catch.txt',
                $macro
            );
        }
    }
    //#703 end maintenance process: Luvina-company 
    //#748 start maintenance process: Luvina-company
    public function getDetailAdvDepId($advDepId) {
        $aryDetailAdvDepId = $aryAdvId = $aryCmpDepId = array();
        if (is_array($advDepId) && count($advDepId) > 0) {
            $cmpDepartmentDao = new CmpDepartmentDao($this->db, $this->backend);
            $aryCmpDepId = array();
            foreach ($advDepId as $key=>$value) {
                $aryAdvDepId = array();
                $aryAdvDepId = explode(',', $value);
                
                $cmpDepId = $aryAdvDepId[1];
                $aryDetailAdvDepId[] = array(
                        'adv_id' => $aryAdvDepId[0],
                        'cmp_dep_id' => $cmpDepId
                        );
                $aryCmpDepId[$cmpDepId] = $cmpDepId;
            }
            
            //Get cmp_id
            $condition = array(
                                array('cmp_dep_id', 'IN', array_values($aryCmpDepId))
                                );
            $colums = array('cmp_id', 'cmp_dep_id');
            $ret = $cmpDepartmentDao->getList($result, $condition, false, false, $colums);
            
            if ($ret === $this->config->config['_DB_OK']) {
                $aryMap = array();
                foreach ($result as $cmpDep) {
                    $aryMap[$cmpDep['cmp_dep_id']] = $cmpDep['cmp_id'];
                }
                
                foreach ($aryDetailAdvDepId as $key=>$value) {
                    $value['cmp_id'] = $aryMap[$value['cmp_dep_id']];
                    $aryDetailAdvDepId[$key] = $value;
                }
            //#750 start maintance process: Luvina-company
            } else {
                $aryDetailAdvDepId = array();
            }
            //#750 start maintance process: Luvina-company
        }
        return $aryDetailAdvDepId;
    }
    //#748 end maintenance process: Luvina-company
    //#763 start maintance process: Luvina-company
    /**
     * auto modifed email
     * @param string $mail
     * @return $email
     */
    public function filterMail($mail) {
        
        if (strlen($mail) == 0) {
            return $mail;
        }
        
        //1. convert fullsize to haftsize and remove Space
        $mail = Kj_Util::convertFullSizeToHalfSize($mail, $delSpace = true);
         
        //2. Remove tab + Remove character not alpha end email
        $patterns = array(
                "/\t/",
                "/(.*)([a-zA-Z]+)([^a-zA-Z]*)$/"
        );
        $replace = array(
                "",
                "\\1\\2"
        );
        
        $mail = preg_replace($patterns, $replace, $mail);
        
        //3. remove [,] to [.] after @
        $mail = preg_replace_callback(
                '/^([^@]+)(@)(.*)/',
                create_function(
                        '$matches',
                        'return $matches[1].$matches[2]. str_replace(",", ".", $matches[3]);'
                ),
                $mail
        );
        
        return $mail;
    }   
    //#763 end maintance process: Luvina-company 
    //#779 start maintance process: Luvina-company
    public function getQueryLastLoginUser($aryAdvDepId = array(), $prefId = null) {
        $query = '';
        $cookie = (array_key_exists("KjMember", $_COOKIE)) ? $_COOKIE['KjMember'] : null;
        if($cookie) {
            $this->autoLogin($cookie);
            /* @var $mgr Kj_ConsumerMypageApplyManager */
            $mgr = $this->backend->getManager('ConsumerMypageApply');
            $aryQuery2Keys = $this->config->get('conversion_query2_keys');
            $aryQuery1Keys = $this->config->get('conversion_query1_keys');
            $aryQueryKeys = (is_array($aryQuery2Keys)) ? $aryQuery2Keys : array();
            $aryQueryKeys = (is_array($aryQuery1Keys)) ? array_merge($aryQueryKeys, $aryQuery1Keys) : $aryQueryKeys;
            if (is_array($aryQueryKeys)) {
                foreach($aryQueryKeys as $key) {
                    if(array_key_exists($key, $_REQUEST)) {
                        $value = $_REQUEST[$key];
                        $conversion .= "&{$key}={$value}";
                        break;
                    }
                }
            }
            $member = $this->getMemberInfo(true);
            if(Ethna::isError($member) || $member === false){
                if ($this->session->isStart()) {
                    $this->session->destroy();
                }
                setcookie('KjMember', '', time() - 3600, '/');
                if ((strpos(getenv("QUERY_STRING"),'act=consumer_entryapply_index') !== false)
                            && is_array($aryAdvDepId) && count($aryAdvDepId) > 0) {
                    $queryAdvDepId = $mgr->makeQueryAdvDepId();
                    $queryAdvDepId .= $conversion;
                    $query = '?act=consumer_entryapply_index'.$queryAdvDepId;
                } else if ((strpos(getenv("QUERY_STRING"),'act=consumer_entryapply_batchindex') !== false)
                        && is_numeric($prefId) && $prefId > 0) {
                    $query = '?act=consumer_entryapply_batchindex&init=1&pref_id='.$prefId.$conversion;
                } else {
                    $query = '?act=consumer_preent';
                }
            } else {
                if ((strpos(getenv("QUERY_STRING"),'act=consumer_entryapply_index') !== false)
                        && is_array($aryAdvDepId) && count($aryAdvDepId) > 0) {
                    if (!$this->isMemTempl()) {
                        $queryAdvDepId = $mgr->makeQueryAdvDepId();
                        $queryAdvDepId .= $conversion;
                        $query = '?act=consumer_mypage_apply_confirm'.$queryAdvDepId;
                    }
                } else if ((strpos(getenv("QUERY_STRING"),'act=consumer_entryapply_batchindex') !== false)
                        && is_numeric($prefId) && $prefId > 0) {
                    $query = '?act=consumer_mypage_batch_confirm&pref_id='.$prefId.$conversion;
                //#910 start maintenance process: Luvina-company
                } else if ((strpos(getenv("QUERY_STRING"),'act=consumer_finpreent') !== false 
                            || strpos(getenv("QUERY_STRING"),'act=visitor_prov_index') !== false) 
                           && $this->isMemTempl()){
                //#910 end maintenance process: Luvina-company
                    $query = '';
                } else {
                    $query = '?act=consumer_mypage_index';
                }
            }
        }
        return $query;
    }
    
    public function autoLogin($cookie) {
        $cryptKey = $this->config->get('crypt_key');
        $blowfish = new Crypt_Blowfish($cryptKey);
        $decryptValue = $blowfish->decrypt($cookie);
        list($cookieMemID, $cookieSession) = split(':', $decryptValue);
        $auto_login = 0;
        if (!$this->session->isStart()) {
            $this->session->start();
            $auto_login = 1;
        }
        $this->session->set('MEM_ID', $cookieMemID);
        if ($auto_login) {
            $this->updateSession();
            $this->setUserAgentForMem($cookieMemID);
        }
        $this->mySession();
        /* @var $mgr Kj_ConsumerMypageApplyManager */
        $mgr = $this->backend->getManager('ConsumerMypageApply');
        $memId = $this->session->get('MEM_ID');
        
        $scoutCount = $mgr->countNewMail($memId, true);
        $this->session->set('NEW_MEM_MEM_RCVMAIL_SCOUT_COUNT', $scoutCount);
        
        $newMailCount = $mgr->countNewMail($memId);
        $this->session->set('NEW_MEM_MEM_RCVMAIL_COUNT', $newMailCount);
        
        $examCount = $mgr->countMemberExamList($memId);
        $this->session->set('NEW_MEM_EXAM_APPLY_COUNT', $examCount);

        $this->session->set('MEM_PREF_ID',$member['mem_pref_id']);
        $this->session->set('MEM_CITY_ID',$member['mem_city_id']);
    }
    //#779 end maintance process: Luvina-company
    
    //#910 start maintenance process: Luvina-company 
    public function getMemberInfoByEmail(&$memInfo, $memMail) {
        $memMasterDao = new MemMasterDao($this->db, $this->backend);
        $condition = array(
                 array('mem_mail', '=', $memMail),
                 array('delete_flg', '=', $this->backend->config->config['_DB_FALSE'] )
        );
        
        $ret = $memMasterDao->getList($result, $condition);
        if($ret != $this->config->config["_DB_OK"]) {
            $memInfo = array();
        } else {
            $memInfo = $result[0];
        }
        return $ret;
    }
    //#910 end maintance process: Luvina-company
      
    //#787 start maintance process: Luvina-company
    public function precedence($cmp_dep_str1, $adv_str2, $preview = false){
        if (strlen(trim($cmp_dep_str1)) > 0 && !$preview) {
            $result = $cmp_dep_str1;
        } else {
            $result = $adv_str2;
        }
        return $result;
    }
    
    public function getDataProjectDetailForApply (&$aryData) {
        $cmpDepartment = new CmpDepartmentDao($this->db,$this->backend);
        /* @var $mgrPublic Kj_PublicManager */
        $mgrPublic = $this->backend->getManager('Public'); 
        
        $mgrPublic->appendToListSimilarAdvs($aryData);
        
        $aryDepId = array();
        
        foreach ($aryData as $key=>$value) {
            $aryDepId[] = $value['cmp_dep_id'];
            $aryData[$key]['adv_emp_type'] = $this->precedence($value['cmp_dep_type'],
                                                                $value['adv_emp_type']
                                                              );
        }
        $aryDetailDep = array();
        $condition = array(
                        array('cmp_dep_id', 'IN', $aryDepId)
                        );
        $code = $cmpDepartment->getCmpDepCity($aryDetailDep, $condition);
        if ($code === $this->config->config['_DB_ERROR'] ||
            $code === $this->config->config['_DB_EMPTY']) {
            return $code;
        }
        foreach ($aryDetailDep as $value) {
            $aryDetailDep[$value['cmp_dep_id']] = $value;
        }
        $depAddress = '';
        foreach ($aryData as $key=>$value) {
            //#806 start maintance process: Luvina-company
            $aryData[$key]['cmp_dep_address_org'] = $aryData[$key]['cmp_dep_address'];
            //#806 end maintance process: Luvina-company
            $depAddress = $mgrPublic->toZipStr($aryDetailDep[$value['cmp_dep_id']]['cmp_dep_zip']);
            $depAddress .= $aryDetailDep[$value['cmp_dep_id']]['sys_pref_namefull'];
            $depAddress .= $aryDetailDep[$value['cmp_dep_id']]['sys_are_city_name'];
            $aryData[$key]['cmp_dep_address'] = $depAddress.$aryData[$key]['cmp_dep_address'];
            
        }
        return $code;
    }
    //#787 end maintance process: Luvina-company
    //#804 start maintance process: Luvina-company
    /**
     * checkExportPrioForSearchLogined
     * @param unknown_type $isPrio
     * @param unknown_type $aryMPrior
     * @param unknown_type $aryPrior
     * @param unknown_type $cndFromInput
     * @param unknown_type $isPrioRandomFlg
     * @param unknown_type $memId
     */
    public function checkExportPrioForSearchLogined(
                        &$isPrio,
                        $aryMPrior,
                        $aryPrior,
                        $cndFromInput = array(), 
                        $isPrioRandomFlg,
                        $memId
    ) {
        $numDate = 45;//Apply in the last 45 days
        $aryAdvDepIdHistory = $this->getHistoryApply($cndFromInput, $numDate, $memId);
        $advDepIdsPrio = array();
        $advDepIdsPrio = $this->getProjectPrio($cndFromInput, $aryAdvDepIdHistory);
        
        if($advDepIdsPrio === $this->config->config['_DB_ERROR']) {
            $isPrio = false;
            return $this->config->config['_DB_ERROR'];
        }
        
        $aryPattern = array(
            _PUBLIC_SEARCH_LOGINED_PATTERN_5,
            //#884 start maintance process: Luvina-company 
            //_PUBLIC_SEARCH_LOGINED_PATTERN_6
            //#884 end maintance process: Luvina-company
        );
        
        foreach ($aryPattern as $pattern) {
            $cndUserPattern[$pattern] = $this->getConditionPattern($pattern, null, null,false);
            //#884 start maintance process: Luvina-company
            if($pattern !== _PUBLIC_SEARCH_LOGINED_PATTERN_OTHER) {
                $cndUserPattern[$pattern][_PUBLIC_SEARCH_CONDITION_NOT_ADV_PRICE] = 1;
            }
            //#884 end maintance process: Luvina-company
        }
        
        if(!$isPrioRandomFlg && is_array($aryPrior) && count($aryPrior) > 0) {
            $aryPrior = $this->checkExistProjectPrio($advDepIdsPrio, $aryPrior);
        }
        if(!$isPrioRandomFlg && is_array($aryMPrior) && count($aryMPrior) > 0) {
            $aryMPrior = $this->checkExistProjectPrio($advDepIdsPrio, $aryMPrior);
        }
        unset($advDepIdsPrio);
        
        if($isPrioRandomFlg) {
            $this->getProjectPriorByPattern5_6($aryMPrior, $aryPrior, $cndFromInput, $cndUserPattern, $aryAdvDepIdHistory);
        }
        
        
        if(count($aryMPrior) > 0 || count($aryPrior) > 0) {
            $isPrio = true;
        } else {
            $isPrio = false;
        }
        
        return $this->config->config['_DB_OK'];
    }
    //#804 end maintance process: Luvina-company
    //#890 start maintance process: Luvina-company #comment:5
    public function getConditionPatternAdvNominalPrice($dataUser) {
        $cndUser = array();
        $cndUser[_PUBLIC_SEARCH_CONDITION_PREF] = null;
        $cndUser[_PUBLIC_SEARCH_CONDITION_CITY] = null;
        $cndUser[_FEATURE_SEARCH_CONDITION_SERVICE] = null;
        $cndUser[_PUBLIC_SEARCH_CONDITION_FORM] = null;
        $cndUser[_PUBLIC_SEARCH_CONDITION_POSITION] = $dataUser['pos'];
        $cndUser[_PUBLIC_SEARCH_CONDITION_EQUAL_ADV_PRICE] = 1;
        $aryCityNear = array();
        $aryCity = array();
        if(is_array($dataUser['city']) && count($dataUser['city'])) {
            $aryCity = $dataUser['city'];
            $aryCityNear = $this->getNearAreaByArrayCityIs($dataUser['city'], false);
            if ($aryCityNear === $this->config->config['_DB_ERROR']) {
                return $this->config->config['_DB_ERROR'];
            }
            //#895 start maintance process: Luvina-company:【Bug ID: 2614】 comment:15
            $aryPrefNear = $this->getPrefOfCityNotNearArea($aryCity);
            if ($aryPrefNear === $this->config->config['_DB_ERROR']) {
                return $this->config->config['_DB_ERROR'];
            }
            $cndUser[_PUBLIC_SEARCH_CONDITION_PREF_NEAR_AREA] = $aryPrefNear;
            //#895 end maintance process: Luvina-company:【Bug ID: 2614】 comment:15
            
            if(is_array($aryCityNear) && count($aryCityNear) > 0) {
                $aryCity = array_merge($aryCity, $aryCityNear);
            }
        }
        $cndUser[_PUBLIC_SEARCH_CONDITION_CITY] = array_unique($aryCity);
        return $cndUser;
    }
    //#890 end maintance process: Luvina-company #comment:5
    //#918 start maintenance process: Luvina-company
    public function getAgentBySiteStatus() {
        $aryConfigAgent = array(
                '1' => 'MB',
                '2' => 'PC',
                '3' => 'SP',
            );
        $mem_id = $this->session->get('MEM_ID');
        $memMasterDao = new MemMasterDao($this->db, $this->backend);
        $condition = array(array('mem_id', '=', $mem_id));
        $columns = array('mem_id', 'site_status');
        $ret = $memMasterDao->getList($result, $condition, false, false, $columns);
        if ($ret === $this->config->config['_DB_OK']) {
            $site_status = $result[0]['site_status'];
            return $aryConfigAgent[$site_status];
        }
    }
    public function sendMailRecruitment($mem_mail, $aryData) {
        
        $mem_need_recruitment_flg = $aryData['mem_need_recruitment_flg'];
        
        if (isset($mem_need_recruitment_flg) && $mem_need_recruitment_flg == 1) {
            
            $action = $this->backend->ctl->getCurrentActionName();
            
            $agent = $this->getAgentBySiteStatus();
            
            $this->importConfigFile('/conf/web/notify_mail_ini.php');
            
            $aryConfig = $this->config->get('notify_email');
            $sms_admin_mail = $this->config->get('sms_admin_mail');
            $email_format = $this->config->get('email_format');
            
            $templateUser = $aryConfig[$action][$agent]['template'];
            $templateAdmin = $aryConfig[$action]['Admin']['template'];
            if (strcmp($email_format, 'text') === 0) {
                $templateUser = $templateUser . '.txt';
                $templateAdmin = $templateAdmin . '.txt';
            } elseif(strcmp($email_format, 'html') === 0) {
                $templateUser = $templateUser . '.html';
                $templateAdmin = $templateAdmin . '.html';
            }
            
            $subjectUser = (isset($aryConfig[$action][$agent]['subject'])) ? $aryConfig[$action][$agent]['subject'] : '';
            $subjectAdmin = (isset($aryConfig[$action]['Admin']['subject']) ? $aryConfig[$action]['Admin']['subject'] : '');
            
            $newSrvUser = (isset($aryConfig[$action][$agent]['new_srv'])) ? $aryConfig[$action][$agent]['new_srv'] : '';
            $newSrvAdmin = (isset($aryConfig[$action]['Admin']['new_srv'])) ? $aryConfig[$action]['Admin']['new_srv'] : '';
            
            $ssUser = (isset($aryConfig[$action][$agent]['ss'])) ? $aryConfig[$action][$agent]['ss'] : '';
            $ssAdmin = (isset($aryConfig[$action]['Admin']['ss'])) ? $aryConfig[$action]['Admin']['ss'] : '';
            
            $fromMailUserTmp = $aryConfig[$action][$agent]['mail_from'];
            $fromMailAdminTmp = $aryConfig[$action]['Admin']['mail_from'];
            $fromMailUser = (isset($fromMailUserTmp) && strlen($fromMailUserTmp) > 0) ?
                                $fromMailUserTmp : $sms_admin_mail;
            $fromMailAdmin = (isset($fromMailAdminTmp) && strlen($fromMailAdminTmp) > 0) ?
                                $fromMailAdminTmp : $sms_admin_mail;;
            
            $filepathUser =  $this->config->get('home_dir') . '/template/ja/mail/' . $templateUser;
            $filepathAdmin =  $this->config->get('home_dir') . '/template/ja/mail/' . $templateAdmin;
            
            if (!file_exists($filepathUser) || !file_exists($filepathAdmin)) {
                $this->logger->log(LOG_ERR, "通知メールで使用するテンプレートが見つかりません。アクション名：{$action}");
                return;
            }
            
            $aryMacro = array(
                'user' => array(
                    'macro' => array(
                            'subject' => $subjectUser,
                            'mem_name' => $aryData['mem_name'],
                            'from_mail' => $fromMailUser,
                            'new_srv' => $newSrvUser,
                            'ss' => $ssUser,
                        ),
                    'info' => array(
                            'template' => $templateUser,
                            'mail_adress' => $mem_mail,
                        ),
                ),
                'admin' => array(
                    'macro' => array(
                            'subject' => $subjectAdmin,
                            'mem_id' => $aryData['mem_id'],
                            'mem_name' => $aryData['mem_name'],
                            'from_mail' => $fromMailAdmin,
                            'new_srv' => $newSrvAdmin,
                            'ss' => $ssAdmin,
                        ),
                    'info' => array(
                            'template' => $templateAdmin,
                            'mail_adress' => $this->config->get('sms_recruitment_admin_mail'),
                        ),
                ),
            );
            
            $ethna_mail =& new Kj_MailSender($this->backend);
            foreach ($aryMacro as $key=>$val) {
                $ethna_mail->send(
                        $val['info']['mail_adress'],
                        $val['info']['template'],
                        $val['macro']
                    );
            }
        }
    }
    //#918 end maintenance process: Luvina-company
    //#967 start maintenance process: Luvina-company
    public function getMemberInfoById(&$result, $condition, $columns = array()) {
        $dao = new MemMasterDao($this->db, $this->backend);
        $ret = $dao->findMemMUser($result, $condition, false, $columns);
        return $ret;
    }
    //#967 end maintenance process: Luvina-company
    //#1000 start maintenace process: Luvina-company
    public function getFullInforMemById(&$memInfo, $memId){
        $dao = new  MemMasterDao($this->db, $this->backend);
        $ret = $dao->getFullInfoMemById($memInfo, $memId);
        return $ret;
    }
    //#1000 end maintenace process: Luvina-company
}

?>