<?php

namespace App\Model;

use PDO;

class BiensManager extends AbstractManager
{
    public const TABLE = 'biens';

    public function add(array $post)
    {
        $fields = array_keys($post);
        $values = [];

        // Création des colonnes de la requêtes
        $query = "INSERT INTO " . self::TABLE . " (" . implode(',', $fields) . ") ";

        foreach ($fields as $field) {
            $values[] = ":$field";
        }

        // Ajout des valeurs à la requête
        $query .= "VALUES (" . implode(',', $values) . ")";

        $statement = $this->pdo->prepare($query);

        foreach ($post as $field => $value) {
            $statement->bindValue(":$field", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        return $statement->execute();
    }

    public function getLastAdd(): array
    {
        $query = "SELECT id FROM " . self::TABLE . " ORDER BY ID DESC LIMIT 0, 1";
        $statement = $this->pdo->prepare($query);
        $statement->bindValue(':id', $this->pdo->lastInsertId(), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function selectAllByCategory(int $category)
    {
        $statement = $this->pdo->prepare("SELECT * FROM " . self::TABLE . " WHERE besoin_id = :category;");
        $statement->bindValue(':category', $category, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
    public function upDate(array $post, int $id)
    {
         // Création de la requêtes
        $query = "UPDATE " . self::TABLE . " SET ";
        $count = 0;
        // Ajout des collones à modifier ansi que leur nouvelle valeurs à la requête
        foreach ($post as $field => $value) {
            if ($count !== (count($post) - 1)) {
                $query .= "$field = '$value' , ";
                $count += 1;
            } else {
                $query .= "$field = '$value' ";
            }
        }

        // Ajout de la condition pour modifier
        $query .= "WHERE id = :id";

        $statement = $this->pdo->prepare($query);

        $statement->bindValue(':id', $id, PDO::PARAM_INT);

        return $statement->execute();
    }

    public function del(int $id)
    {
        $query = "DELETE FROM " . self::TABLE . " WHERE id = :id";

        $statement = $this->pdo->prepare($query);

        $statement->bindValue(":id", $id, PDO::PARAM_INT);

        return $statement->execute();
    }
    /**
     * Mets à jour la photo par défault d'un bien
     */
    public function updateMainPicture(int $idBien, string $pictureName): bool
    {
        $statement = $this->pdo->prepare("UPDATE " . self::TABLE .
            " SET photo_principale = :photo_principale WHERE id = :id;");
        $statement->bindValue(':id', $idBien, PDO::PARAM_INT);
        $statement->bindValue(':photo_principale', $pictureName);

        return $statement->execute();
    }
}
