<?php

/**
 * Created by PhpStorm.
 * User: aurelwcs
 * Date: 08/04/19
 * Time: 18:40
 */

namespace App\Controller;

use Exception;
use DirectoryIterator;
use App\Model\DocumentManager;
use App\Model\BiensManager;
use App\Model\TypesManager;
use App\Model\SloganManager;
use App\Model\MessagesManager;

class AdminController extends AbstractController
{
    private BiensManager $biensManager;
    private TypesManager $typesManager;
    private MessagesManager $messagesManager;

    public function __construct()
    {
        parent::__construct();
        $this->biensManager = new BiensManager();
        $this->typesManager = new TypesManager();
        $this->messagesManager = new MessagesManager();
    }
    /**
     * Display home page
     *
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */

    public function loggin()
    {
        $this->startSession();
        $this->login();
        return $this->twig->render('Admin/loggin.html.twig');
    }
    public function index()
    {
        $this->startSession();
        $this->authorizeAccess();
        $this->logout();
        return $this->twig->render('Admin/index.html.twig', [
            "user" => $_SESSION['fullname'],
            "role" => $_SESSION['role'],
        ]);
    }

    // --------------- Fonctions annonces -----------------------

    public function ajoutAnnonce()
    {
        $this->startSession();
        $this->authorizeAccess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $_POST;

            // Champs checkbox du formulaire
            $choices = [
                'residence',
                'duplex',
                'ascenseur',
                'entree',
                'sejour',
                'salon',
                'double_vitrage',
                'volets_roulants',
                'gardien',
                'calme',
                'ensoleille',
                'logia',
                'cave',
                'grenier',
                'sous_sol',
                'cellier',
                'balcon',
                'cheminee',
                'piscine',
                'interphone',
                'digicode',
                'terrasse',
                'cour',
                'cour_close',
                'jardin',
                'jardin_clos',
                'parking',
                'garage',
                'local_velo',
            ];

            // Pour chaque checkbox,  on stocke la valeur si checkée, sinon 'non'
            foreach ($choices as $choice) {
                $post[$choice] = isset($_POST[$choice]) ? $_POST[$choice] : 'non';
            }

            $post['date'] = date('Y-m-d');

            $this->biensManager->add($post);
            header('Location: /admin/annonceAjouter');
        }

        return $this->twig->render('Admin/ajoutAnnonce.html.twig', [
            'besoins' => $this->typesManager->getByTypes('besoin'),
            'categories' => $this->typesManager->getByTypes('categorie'),
            'types' => $this->typesManager->getByTypes('type'),
            'etats' => $this->typesManager->getByTypes('etat'),
            'chauffages' => $this->typesManager->getByTypes('chauffage'),
            'cuisines' => $this->typesManager->getByTypes('cuisine'),
            'revetements' => $this->typesManager->getByTypes('revetement'),
            "user" => $_SESSION['fullname'],
            "role" => $_SESSION['role'],
        ]);
    }

    public function modifAnnonce()
    {
        $this->startSession();
        $this->authorizeAccess();

        $id = $_GET['id'];


        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $post = $_POST;
            $this->biensManager->upDate($post, $id);
            header('Location: listAnnonce');
        }

        return $this->twig->render('Admin/modifAnnonce.html.twig', [
            'bien' => $this->biensManager->selectOneById($id),
            "user" => $_SESSION['fullname'],
            "role" => $_SESSION['role'],
        ]);
    }

    public function supprimerAnnonce()
    {
        $this->startSession();
        $this->authorizeAccess();

        $id = $_GET['id'];


        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->biensManager->delete($id);
            $this->deleteDirectory(realpath(__DIR__ . '/../../public/assets/images/annonces/' . $id));
        }

        header('Location: ' . $_SERVER['HTTP_REFERER']);
    }

    // ------------------------------------------------------------------------------------

    public function modifSlogan()
    {
        $this->startSession();
        $this->authorizeAccess();

        $sloganManager = new SloganManager();

        $done = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newlistSlogan = $_POST;
            $sloganManager->modifyListSlogan($newlistSlogan);
            $done = true;
        }

        $listSlogans = $sloganManager->selectAll();

        return $this->twig->render('Admin/modifSlogan.html.twig', [
            'listSlogans' => $listSlogans,
            "done" => $done,
            "user" => $_SESSION['fullname'],
            "role" => $_SESSION['role'],
            ]);
    }
    public function modifDocument()
    {
        $this->startSession();
        $this->authorizeAccess();

        $documentManager = new DocumentManager();
        $done = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newlistDoc = $_POST;
            $documentManager->modifyListDoc($newlistDoc);
            $done = true;
        }
        $listDocs = $documentManager->selectAll();
        return $this->twig->render('Admin/modifDocument.html.twig', [
            "lists" => $listDocs,
            "done" => $done,
            "user" => $_SESSION['fullname'],
            "role" => $_SESSION['role'],
            ]);
    }
    public function ajoutPhoto()
    {
        $this->startSession();
        $this->authorizeAccess();
        $this->setAnnonceId();
        $this->setMkDir();
        $this->setImgFolder();
        $imageUrl = '';
        $folderContent = $this->getImgFolderContent();
        $imageFolder = "../assets/images/annonces/" . $this->annonceId . "/";
        $error = '';

        try {
            $imageUrl = $this->upload();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        $this->deleteImg();

        return $this->twig->render('Admin/ajoutphoto.html.twig', [
            'imageUrl' => $imageUrl,
            'error' => $error,
            'id' => $this->annonceId,
            'imagesList' => $folderContent,
            'imagesFolder' => $imageFolder,
            'post' => $_POST,
            "user" => $_SESSION['fullname'],
            "role" => $_SESSION['role'],
        ]);
    }
    // fonction d'ajout des images par formulaire
    private function upload()
    {
        //check methode serveur
        if ($_SERVER["REQUEST_METHOD"] === "POST" && (!empty($_FILES))) {
            if (!empty($_GET)) {
                $this->annonceId = $_GET['id'];
            }

            //recup extension fichier
            $extension = pathinfo($_FILES['pictureUpload']['name'], PATHINFO_EXTENSION);
            $uploadFile = $this->uploadDir . basename($_FILES['pictureUpload']['name']);

            $uploadedImgBaseName = basename($uploadFile);

            //set liste d'extensions
            $extensionsOk = ['jpg', 'jpeg', 'png'];

            //check extension du fichier vs extensions autorisées
            if (!in_array($extension, $extensionsOk)) {
                throw new Exception('L\'image doit etre de type jpeg, jpg ou png');
            }

            //set chemin destination fichier
            if (!empty($_POST['setAsMain'])) {
                $this->biensManager->updateMainPicture($this->annonceId, $_FILES['pictureUpload']['name']);
            }

            move_uploaded_file($_FILES['pictureUpload']['tmp_name'], $uploadFile);

            return $uploadedImgBaseName;
        }
    }

    public function annonceAjouter()
    {
        $this->startSession();
        $this->authorizeAccess();

        return $this->twig->render('Admin/annonceAjouter.html.twig', [
            'id' => $this->biensManager->getLastAdd(),
            "user" => $_SESSION['fullname'],
            "role" => $_SESSION['role'],
        ]);
    }

    private function deleteDirectory($path)
    {
        try {
            $iterator = new DirectoryIterator($path);

            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isDot()) {
                    continue;
                }

                if ($fileinfo->isDir()) {
                    if ($this->deleteDirectory($fileinfo->getPathname())) {
                        rmdir($fileinfo->getPathname());
                    }
                }

                if ($fileinfo->isFile()) {
                    unlink($fileinfo->getPathname());
                }
            }

            rmdir($path);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    protected function deleteImg()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['deleteImg'])) {
            unlink($this->uploadDir . "/" . $_POST['deleteImg']);
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        }
    }
}
