<?php

class tiezi
{
    function __construct()
    {

    }

    public function index()
    {
        $id = $_GET['bk'];
        $bk = &$id;
        if (empty($id)) {
            exit ("参数错误！");
        }

        //开始分页大小
        $page_size = 5;

        //获取当前页码
        $page_num = empty($_GET['page']) ? 1 : $_GET['page'];

        //计算记录总数
        $sql = "select count(*) as c from " . DB_PRE . "post where cid='$bk'";
        $row = mysql_func($sql);
        $count = $row[0]['c'];

        //计算记录总页数
        $page_count = ceil($count / $page_size);
        //防止越界
        if ($page_num >= $page_count) {
            $page_num = $page_count;
        }

        if ($page_num <= 0) {
            $page_num = 1;
        }

        //准备SQL语句
        $limit = " limit " . (($page_num - 1) * $page_size) . "," . $page_size;

        $sql = "select p.*,u.username from " . DB_PRE . "post as p," . DB_PRE . "user as u where  p.cid=" . $id . " and u.id=p.uid and p.cid='$bk'" . $limit;
        //$sql = "select * from ".DB_PRE."post where cid='$bk'".$limit;
        //$sql = "select * from ".DB_PRE."post where cid='2'";
        $row = mysql_func($sql);
        foreach ($row as $k => $post) {
            $reply_count_sql = "select count(id) as count from bbs_reply where pid={$post['id']} ";
            $row[$k]['reply_count'] = mysql_func($reply_count_sql)[0]['count'];

        }
        $data['row'] = $row;
        $data['bk'] = $bk;
        $data['count'] = $count;
        $data['page_size'] = $page_size;
        $data['page_count'] = $page_count;
        $data['page_num'] = $page_num;
        displayTpl('tiezi/index', $data);
    }

    public function detail()
    {
        $zt = $_GET['zt'];
        if (empty($zt)) {
            exit ("参数1错误！");
        }
        $bk = $_GET['bk'];
        if (empty($bk)) {
            exit ("参数2错误！");
        }
        $sql = "select p.*,u.*,d.* from " . DB_PRE . "post as p," . DB_PRE . "user as u," . DB_PRE . "user_detail as d where p.uid=u.id and d.uid=p.uid and p.id='$zt'";
        $row = mysql_func($sql);
        $post = $row[0];
        $reply_count_sql = "select count(id) as count from bbs_reply where pid={$zt} ";
        $post['reply_count'] = mysql_func($reply_count_sql)[0]['count'];

        //开始分页大小
        $page_size = 5;

        //获取当前页码
        $page_num = empty($_GET['page']) ? 1 : $_GET['page'];

        //计算记录总数
        $sql = "select count(*) as c from " . DB_PRE . "reply ";
        $row = mysql_func($sql);
        $count = $row[0]['c'];

        //计算记录总页数
        $page_count = ceil($count / $page_size);
        //防止越界
        if ($page_num >= $page_count) {
            $page_num = $page_count;
        }
        if ($page_num <= 0) {
            $page_num = 1;
        }


        //准备SQL语句
        $limit = " limit " . (($page_num - 1) * $page_size) . "," . $page_size;;
        $sql = "select r.*,u.*,d.* from " . DB_PRE . "reply as r," . DB_PRE . "user as u," . DB_PRE . "user_detail as d where r.uid=u.id and d.uid=r.uid and r.pid='$zt'" . $limit;
        $row = mysql_func($sql);

        $data['bk'] = $bk;
        $data['zt'] = $zt;
        $data['post'] = $post;
        $data['row'] = $row;
        $data['count'] = $count;
        $data['page_size'] = $page_size;
        $data['page_count'] = $page_count;
        $data['page_num'] = $page_num;
        displayTpl('tiezi/detail', $data);
    }


    public function reply()
    {
        if (!isset($_GET['bk'])) {
            exit ("参数错误！");
        }
        if (!isset($_GET['zt'])) {
            exit ("参数错误！");
        }
        $bk = $_GET['bk'];
        $zt = $_GET['zt'];
        if (isset($_POST['id'])) {
            $pid = $_POST['id'];
            $content = $_POST['content'];
            $username = $_SESSION['home']['username'];
            $ptime = $_SERVER['REQUEST_TIME'];
            $pip = ip2long($_SERVER['REMOTE_ADDR']);


            $sql = "select * from " . DB_PRE . "iprefuse";
            $row = mysql_func($sql);
            foreach ($row as $ip) {
                if ($pip >= $ip['ipmin'] && $pip <= $ip['ipmax']) {
                    echo "<script>alert('你所在的IP已被禁止发帖！')</script>";
                    echo "<script>window.location.href='post.php?bk=" . $bk . "&zt=" . $zt . "'</script>";
                    exit;
                }
            }

            $sql = "select u.id,u.username from " . DB_PRE . "user as u where username='" . $username['username'] . "'";
            $row = mysql_func($sql);
            if (!$row) {
                echo "请先登入！";
                echo "<script>window.location.href='".url('user/login')."'</script>";
                exit;
            }
            $uid = $row[0]['id'];

            $sql = "insert into " . DB_PRE . "reply(pid,content,uid,ptime,pip) value('$pid','$content','$uid','$ptime','$pip')";

            $row = mysql_func($sql);

            if (!$row) {
                echo "<script>alert('发帖失败，请稍候再试！')</script>";
                echo "<script>window.location.href='".url('tiezi/reply',array('bk'=>$bk,'zt'=>$zt))."'</script>";
            } else {
                echo "<script>alert('回复成功')</script>";
                echo "<script>window.location.href='".url('tiezi/detail',array('bk'=>$bk,'zt'=>$zt))."'</script>";
            }
        }

        $sql = "select title from " . DB_PRE . "post where id=" . $zt;
        $row1 = mysql_func($sql);
        $row1 = $row1[0];
        $data['title'] = $row1['title'];
        $data['zt'] = $zt;
        $data['bk'] = $bk;
        displayTpl('tiezi/reply', $data);
    }
}
