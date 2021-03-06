<?php declare(strict_types=1);

namespace Reconmap\Controllers\Projects;

use Psr\Http\Message\ServerRequestInterface;
use Reconmap\Controllers\Controller;
use Reconmap\Repositories\ProjectRepository;
use Reconmap\Repositories\SearchCriterias\ProjectSearchCriteria;
use Reconmap\Services\PaginationRequestHandler;

class GetProjectsController extends Controller
{
    public function __construct(private ProjectRepository $projectRepository)
    {
    }

    public function __invoke(ServerRequestInterface $request): array
    {
        $params = $request->getQueryParams();

        $user = $this->getUserFromRequest($request);

        $searchCriteria = new ProjectSearchCriteria();
        if (isset($params['keywords'])) {
            $keywords = $params['keywords'];
            $keywordsLike = "%$keywords%";

            $searchCriteria->addCriterion('(p.name LIKE ? OR p.description LIKE ?)', [$keywordsLike, $keywordsLike]);
        }
        if (isset($params['clientId'])) {
            $searchCriteria->addCriterion('p.client_id = ?', [intval($params['clientId'])]);
        }
        if (isset($params['status'])) {
            $archived = 'archived' === $params['status'];
            $searchCriteria->addCriterion('p.archived = ?', [$archived]);
        }
        if (isset($params['isTemplate'])) {
            $searchCriteria->addTemplateCriterion(intval($params['isTemplate']));
        } else {
            $searchCriteria->addIsNotTemplateCriterion();
        }

        if (!$user->isAdministrator()) {
            $searchCriteria->addCriterion('(p.visibility = "PUBLIC" OR ? IN (SELECT user_id FROM project_user WHERE project_id = p.id))', [$user->id]);
        }

        $paginator = new PaginationRequestHandler($request);
        return $this->projectRepository->search($searchCriteria, $paginator);
    }
}
