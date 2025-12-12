<?php
// src/Controller/ActivityLogController.php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class ActivityLogController extends AbstractController
{
    #[Route('/activity-log', name: 'app_activity_log')]
    public function index(ActivityLogRepository $activityLogRepository, Request $request): Response
    {
        // Get pagination parameters
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        
        // Get filter parameters
        $filters = [
            'username' => $request->query->get('username'),
            'role' => $request->query->get('role'),
            'action' => $request->query->get('action'),
            'entityType' => $request->query->get('entityType'),
            'date' => $request->query->get('date'),
        ];
        
        // Get paginated activity logs
        $paginator = $activityLogRepository->findPaginated($page, $limit, $filters);
        
        // Convert Paginator to array
        $activityLogs = [];
        foreach ($paginator as $log) {
            $activityLogs[] = $log;
        }
        
        $totalItems = $paginator->count();
        $totalPages = ceil($totalItems / $limit);
        
        return $this->render('activitylog.html.twig', [
            'activityLogs' => $activityLogs,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }
    
    #[Route('/activity-log/data', name: 'app_activity_log_data')]
    public function getData(ActivityLogRepository $activityLogRepository, Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        
        $filters = [
            'username' => $request->query->get('username'),
            'role' => $request->query->get('role'),
            'action' => $request->query->get('action'),
            'entityType' => $request->query->get('entityType'),
            'date' => $request->query->get('date'),
        ];
        
        $paginator = $activityLogRepository->findPaginated($page, $limit, $filters);
        
        // Convert Paginator to array
        $activityLogs = [];
        foreach ($paginator as $log) {
            $activityLogs[] = $log;
        }
        
        $totalItems = $paginator->count();
        $totalPages = ceil($totalItems / $limit);
        
        $logsArray = [];
        foreach ($activityLogs as $log) {
            $logsArray[] = [
                'id' => $log->getId(),
                'user_id' => $log->getUserId() ? ['id' => $log->getUserId()->getId()] : null,
                'username' => $log->getUsername(),
                'role' => $log->getRole(),
                'action' => $log->getAction(),
                'entityType' => $log->getEntityType(),
                'target_data' => $log->getTargetData(),
                'ip_address' => $log->getIpAddress(),
                'created_at' => $log->getCreatedAt()->format('c'),
            ];
        }
        
        return $this->json([
            'logs' => $logsArray,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalItems' => $totalItems,
            ]
        ]);
    }
    
    #[Route('/activity-log/export', name: 'app_activity_log_export')]
    public function export(ActivityLogRepository $activityLogRepository, Request $request): Response
    {
        // Get filter parameters from request
        $filters = [
            'username' => $request->query->get('username'),
            'role' => $request->query->get('role'),
            'action' => $request->query->get('action'),
            'entityType' => $request->query->get('entityType'),
            'date' => $request->query->get('date'),
        ];
        
        // Get logs with filters
        $logs = $activityLogRepository->findByFilters($filters);
        
        $csvData = "ID,User ID,Username,Role,Action,Entity Type,Target Data,IP Address,Created At (Manila)\n";
        
        foreach ($logs as $log) {
            $userId = $log->getUserId() ? $log->getUserId()->getId() : '';
            $csvData .= sprintf(
                "%d,%s,%s,%s,%s,%s,\"%s\",%s,%s\n",
                $log->getId(),
                $userId,
                $log->getUsername(),
                $log->getRole(),
                $log->getAction(),
                $log->getEntityType() ?? 'N/A',
                str_replace('"', '""', $log->getTargetData()),
                $log->getIpAddress() ?? 'N/A',
                $log->getCreatedAt()->format('Y-m-d H:i:s')
            );
        }
        
        $response = new Response($csvData);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="activity_logs_' . date('Y-m-d') . '.csv"');
        
        return $response;
    }
}