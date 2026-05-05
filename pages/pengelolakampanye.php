session_start();
include 'koneksi.php';
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
$action = isset($_GET['action']) ? $_GET['action'] : '';
switch ($action) {
    case 'form':
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        echo "Form Pengelola Kampanye";
        break;
    case 'hapus':
        $id = $_GET['id'];
        $res=mysqli_query($koneksi, "SELECT total_dana FROM kampanye WHERE id_kampanye = '$id'");
        $trg=mysqli_fetch_assoc($res);
           if($trg['total_dana'] >= 10000) {echo "<script>alert('Kampanye tidak dapat dihapus karena sudah mencapai target dana');window.location.href='pengelolakampanye.php';</script>";} 
           else {mysqli_query($koneksi, "DELETE FROM kampanye WHERE id_kampanye = '$id'");
                 header("Location: pengelolakampanye.php");}
        break;
   
        default:    
        echo "Daftar Kampanye";
        break;
}
?>