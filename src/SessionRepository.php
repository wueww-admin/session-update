<?php


namespace SessionUpdate;


use Doctrine\DBAL\Types\Type;
use SessionUpdate\DTO\Session;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

class SessionRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }


    /**
     * @param int $id
     * @return Session | null
     * @throws DBALException
     * @throws \Exception
     */
    public function findById(int $id): ?Session
    {
        $sql = "SELECT s.*,
                    sdp.title,
                    sdp.short_description,
                    sdp.long_description,
                    sdp.location_name,
                    sdp.location_lat,
                    sdp.location_lng,
                    sdp.link
                FROM sessions s
                INNER JOIN session_details sdp on s.proposed_details = sdp.id
                WHERE s.id = :id";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue('id', $id);
        $stmt->execute();

        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return Session::fromRecord($data);
    }

    /**
     * @param int $userId
     * @param Session $session
     * @return int
     * @throws DBALException
     */
    public function create(int $userId, Session $session): int
    {
        $this->connection->insert('session_details', [
            'title' => $session->getTitle(),
            'short_description' => $session->getShortDescription(),
            'long_description' => $session->getLongDescription(),
            'location_name' => $session->getLocationName(),
            'location_lat' => $session->getLocationLat(),
            'location_lng' => $session->getLocationLng(),
            'link' => $session->getLink(),
        ]);

        $sessionDetailId = $this->connection->lastInsertId();

        $this->connection->insert('sessions', [
            'start' => $session->getStart(),
            'end' => $session->getEnd(),
            'owner' => $userId,
            'proposed_details' => $sessionDetailId,
        ], [
            Type::DATETIME,
            Type::DATETIME,
            Type::INTEGER,
            Type::INTEGER,
        ]);

        return (int)$this->connection->lastInsertId();
    }

    /**
     * @param Session $sessionOld
     * @param Session $sessionNew
     * @throws DBALException
     */
    public function update(Session $sessionOld, Session $sessionNew)
    {
        $updates = [
            'start' => $sessionNew->getStart(),
            'end' => $sessionNew->getEnd(),
            'cancelled' => $sessionNew->isCancelled(),
        ];

        $types = [
            Type::DATETIME,
            Type::DATETIME,
            Type::BOOLEAN,
        ];

        if (!$sessionOld->equalDetails($sessionNew)) {
            $this->connection->insert('session_details', [
                'title' => $sessionNew->getTitle(),
                'short_description' => $sessionNew->getShortDescription(),
                'long_description' => $sessionNew->getLongDescription(),
                'location_name' => $sessionNew->getLocationName(),
                'location_lat' => $sessionNew->getLocationLat(),
                'location_lng' => $sessionNew->getLocationLng(),
                'link' => $sessionNew->getLink(),
            ]);

            $updates['proposed_details'] = $this->connection->lastInsertId();
            $types[] = Type::INTEGER;
        }

        if (empty($updates)) {
            return;
        }

        $this->connection->update('sessions', $updates, ['id' => $sessionOld->getId()], $types);

        if (isset($updates['proposed_details']) && !$sessionOld->isAccepted()) {
            $this->connection->delete('session_details', ['id' => $sessionOld->getProposedDetailsId()]);
        }
    }
}