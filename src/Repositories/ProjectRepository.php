<?php declare(strict_types=1);

namespace Reconmap\Repositories;

use Reconmap\Repositories\QueryBuilders\UpdateQueryBuilder;

class ProjectRepository extends MysqlRepository
{
    public const UPDATABLE_COLUMNS_TYPES = [
        'client_id' => 'i',
        'name' => 's',
        'description' => 's'
    ];

    public function findAll(): array
    {
        $rs = $this->db->query('SELECT * FROM project LIMIT 20');
        return $rs->fetch_all(MYSQLI_ASSOC);
    }

    public function findById(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM project WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $rs = $stmt->get_result();
        $project = $rs->fetch_assoc();
        $stmt->close();

        return $project;
    }

    public function findTemplateProjects(int $isTemplate): array
    {
        $sql = <<<SQL
        SELECT
            p.*,
            c.name AS client_name,
            (SELECT COUNT(*) FROM task WHERE project_id = p.id) AS num_tasks
        FROM project p
        LEFT JOIN client c ON (c.id = p.client_id)
        WHERE p.is_template = ?
        SQL;
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $isTemplate);
        $stmt->execute();
        $rs = $stmt->get_result();
        $projects = $rs->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $projects;
    }

    public function createFromTemplate(int $templateId): array
    {
        $this->db->begin_transaction();

        $projectSql = <<<SQL
        INSERT INTO project (name, description) SELECT CONCAT(name, ' - ', CURRENT_TIMESTAMP()), description FROM project WHERE id = ?
        SQL;
        $stmt = $this->db->prepare($projectSql);
        $stmt->bind_param('i', $templateId);
        $projectId = $this->executeInsertStatement($stmt);

        $tasksSql = <<<SQL
        INSERT INTO task (project_id, command, command_parser, name, description) SELECT ?, command, command_parser, name, description FROM task WHERE project_id = ?
        SQL;
        $stmt = $this->db->prepare($tasksSql);
        $stmt->bind_param('ii', $projectId, $templateId);
        $this->executeInsertStatement($stmt);

        $this->db->commit();

        return [
            'projectId' => $projectId
        ];
    }

    public function insert(object $project): int
    {
        $stmt = $this->db->prepare('INSERT INTO project (client_id, name, description, is_template) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('issi', $project->clientId, $project->name, $project->description, $project->isTemplate);
        return $this->executeInsertStatement($stmt);
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM project WHERE id = ?');
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $success = $result && 1 === $stmt->affected_rows;
        $stmt->close();

        return $success;
    }

    public function updateById(int $id, array $newColumnValues): bool
    {
        $updateQueryBuilder = new UpdateQueryBuilder('project');
        $updateQueryBuilder->setColumnValues(array_map(fn() => '?', $newColumnValues));
        $updateQueryBuilder->setWhereConditions('id = ?');

        $stmt = $this->db->prepare($updateQueryBuilder->toSql());
        call_user_func_array([$stmt, 'bind_param'], [$this->generateParamTypes(array_keys($newColumnValues)) . 'i', ...$this->refValues($newColumnValues), &$id]);
        $result = $stmt->execute();
        $success = $result && 1 === $stmt->affected_rows;
        $stmt->close();

        return $success;
    }
}
