<?php
// controllers/JournalController.php

declare(strict_types=1);

class JournalController extends Controller
{
    public function index(): void
    {
        $journalModel = new JournalModel();
        $scrutinModel = new ScrutinModel();

        $filters = [
            'action'     => $_GET['f_action'] ?? '',
            'id_scrutin' => $_GET['f_scrutin'] ?? '',
            'cni'        => $_GET['f_cni'] ?? '',
        ];

        $this->render('admin/journal/index', [
            'entrees'  => $journalModel->getFiltered($filters, 200),
            'scrutins' => $scrutinModel->getAll(),
            'filters'  => $filters,
        ], 'admin');
    }
}