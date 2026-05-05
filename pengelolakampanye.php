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
        echo "Form Pengelola Kampanye"
        break;
    case 'edit':
        include 'edit_kampanye.php';
        break;
    case 'delete':
        $id = $_GET['id'];
        $query = "DELETE FROM kampanye WHERE id = '$id'";
        mysqli_query($koneksi, $query);
        header("Location: pengelolakampanye.php");
        break;
    default:
        $query = "SELECT * FROM kampanye";
        $result = mysqli_query($koneksi, $query);
        include 'list_kampanye.php';
        break;
}
?>