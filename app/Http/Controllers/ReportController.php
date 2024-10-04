<?php

namespace App\Http\Controllers;

use App\Enums\StoryStatus;
use App\Enums\StoryType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request, $year = null)
    {
        // Recupera l'anno e i quarter disponibili tramite una funzione separata
        [$year, $availableQuarters, $error] = $this->getYearAndQuarters($year);

        // Se c'è un errore (ad esempio, l'anno è nel futuro), lo restituiamo subito
        if ($error) {
            return view('reports.index')->with('error', $error);
        }
        $developers = $this->getDevelopers();
        $customers = $this->getCustomers();
        $tags = $this->getTags();

        // Ottieni i report per Tipo e Stato tramite funzioni separate
        $reportByType = $this->generateReportByType($year, $availableQuarters);
        [$reportByStatus, $totals] = $this->generateReportByStatus($year, $availableQuarters); // Ora include i totali
        // Ottieni i report per Utente e somma totale
        $reportByUser = $this->generateReportByUser($year, $availableQuarters, $developers);
        $reportByCustomer = $this->generateReportByCustomer($year, $availableQuarters, $customers);
        $reportByTag = $this->generateReportByTag($year, $availableQuarters, $tags, $customers);
        $reportByStatusUser = $this->generateReportByStatusUser($year, $availableQuarters, $developers);
        $reportByStatusCustomer = $this->generateReportByStatusCustomer($year, $availableQuarters, $customers);
        $reportByStatusTag = $this->generateReportByStatusTag($year, $availableQuarters, $tags, $customers);
        $reportByTagType = $this->generateReportByTagType($year, $availableQuarters, $tags, $customers);

        return view('reports.index', compact('reportByType', 'reportByStatus', 'totals', 'year', 'availableQuarters', 'reportByUser', 'developers', 'reportByStatusUser', 'reportByCustomer', 'reportByStatusCustomer', 'reportByTag', 'reportByStatusTag', 'reportByTagType'));
    }
    /**
     * Genera il report per Tipo di Storia
     */
    private function generateReportByType($year, $availableQuarters)
    {
        $totalStories = $year === 'All Time' ? Story::count() : Story::whereYear('updated_at', $year)->count();

        $reportByType = [];
        foreach (StoryType::cases() as $type) {
            $yearTotal = $year === 'All Time' ? Story::where('type', $type->value)->count() : Story::where('type', $type->value)->whereYear('updated_at', $year)->count();
            $q1 = $year === 'All Time' ? Story::where('type', $type->value)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 1')->count() : Story::where('type', $type->value)->whereYear('updated_at', $year)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 1')->count();
            $q2 = $year === 'All Time' ? Story::where('type', $type->value)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 2')->count() : Story::where('type', $type->value)->whereYear('updated_at', $year)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 2')->count();
            $q3 = $year === 'All Time' ? Story::where('type', $type->value)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 3')->count() : Story::where('type', $type->value)->whereYear('updated_at', $year)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 3')->count();
            $q4 = $year === 'All Time' ? Story::where('type', $type->value)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 4')->count() : Story::where('type', $type->value)->whereYear('updated_at', $year)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 4')->count();

            // Calcola la percentuale rispetto al totale
            $yearPercentage = $totalStories > 0 ? ($yearTotal / $totalStories) * 100 : 0;
            $q1Percentage = $totalStories > 0 ? ($q1 / $totalStories) * 100 : 0;
            $q2Percentage = $totalStories > 0 ? ($q2 / $totalStories) * 100 : 0;
            $q3Percentage = $totalStories > 0 ? ($q3 / $totalStories) * 100 : 0;
            $q4Percentage = $totalStories > 0 ? ($q4 / $totalStories) * 100 : 0;

            $reportByType[] = [
                'type' => $type->value,
                'year_total' => $yearTotal,
                'year_percentage' => $yearPercentage,
                'q1' => $q1,
                'q1_percentage' => $q1Percentage,
                'q2' => $q2,
                'q2_percentage' => $q2Percentage,
                'q3' => $q3,
                'q3_percentage' => $q3Percentage,
                'q4' => $q4,
                'q4_percentage' => $q4Percentage,
            ];
        }

        return $reportByType;
    }
    /**
     * Genera il report per Stato di Storia
     */
    private function generateReportByStatus($year, $availableQuarters)
    {
        $totalStories = $year === 'All Time' ? Story::count() : Story::whereYear('updated_at', $year)->count();

        $reportByStatus = [];
        $totals = [
            'year_total' => 0,
            'q1' => 0,
            'q2' => 0,
            'q3' => 0,
            'q4' => 0,
        ];

        foreach (StoryStatus::cases() as $status) {
            $yearTotal = $year === 'All Time' ? Story::where('status', $status->value)->count() : Story::where('status', $status->value)->whereYear('updated_at', $year)->count();
            $q1 = $year === 'All Time' ? Story::where('status', $status->value)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 1')->count() : Story::where('status', $status->value)->whereYear('updated_at', $year)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 1')->count();
            $q2 = $year === 'All Time' ? Story::where('status', $status->value)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 2')->count() : Story::where('status', $status->value)->whereYear('updated_at', $year)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 2')->count();
            $q3 = $year === 'All Time' ? Story::where('status', $status->value)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 3')->count() : Story::where('status', $status->value)->whereYear('updated_at', $year)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 3')->count();
            $q4 = $year === 'All Time' ? Story::where('status', $status->value)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 4')->count() : Story::where('status', $status->value)->whereYear('updated_at', $year)->whereRaw('EXTRACT(QUARTER FROM updated_at) = 4')->count();

            // Calcola la percentuale rispetto al totale
            $yearPercentage = $totalStories > 0 ? ($yearTotal / $totalStories) * 100 : 0;
            $q1Percentage = $totalStories > 0 ? ($q1 / $totalStories) * 100 : 0;
            $q2Percentage = $totalStories > 0 ? ($q2 / $totalStories) * 100 : 0;
            $q3Percentage = $totalStories > 0 ? ($q3 / $totalStories) * 100 : 0;
            $q4Percentage = $totalStories > 0 ? ($q4 / $totalStories) * 100 : 0;

            // Aggiorna i totali
            $totals['year_total'] += $yearTotal;
            $totals['q1'] += $q1;
            $totals['q2'] += $q2;
            $totals['q3'] += $q3;
            $totals['q4'] += $q4;

            $reportByStatus[] = [
                'status' => $status->value,
                'year_total' => $yearTotal,
                'year_percentage' => $yearPercentage,
                'q1' => $q1,
                'q1_percentage' => $q1Percentage,
                'q2' => $q2,
                'q2_percentage' => $q2Percentage,
                'q3' => $q3,
                'q3_percentage' => $q3Percentage,
                'q4' => $q4,
                'q4_percentage' => $q4Percentage,
            ];
        }

        return [$reportByStatus, $totals]; // Restituisci anche i totali
    }
    /**
     * Funzione per determinare l'anno e i quarter disponibili
     */
    private function getYearAndQuarters($year)
    {
        $currentYear = Carbon::now()->year;
        $currentQuarter = Carbon::now()->quarter;

        // Se non viene passato un anno, visualizza "All Time" e considera tutti i dati disponibili
        if (!$year) {
            return ['All Time', [1, 2, 3, 4], null]; // Nessun errore, tutti i quarter sono disponibili
        }

        // Se l'anno passato è nel futuro, restituiamo un errore
        if ($year > $currentYear) {
            return [$year, [], 'Nessun dato disponibile per il futuro.'];
        }

        // Se l'anno è quello corrente, restituiamo solo i quarter fino al corrente
        $availableQuarters = $year == $currentYear ? range(1, $currentQuarter) : [1, 2, 3, 4];

        return [$year, $availableQuarters, null]; // Nessun errore
    }

    private function getDevelopers()
    {
        $developers = User::whereJsonContains('roles', UserRole::Developer)
            ->whereHas('stories')  // Verifica che l'utente abbia storie associate
            ->distinct()
            ->get();
        return $developers;
    }
    private function getCustomers()
    {
        return Story::whereNotNull('creator_id')
            ->whereHas('creator', function ($query) {
                $query->whereJsonContains('roles', UserRole::Customer); // Filtra utenti con il ruolo 'Customer'
            })
            ->selectRaw('creator_id, COUNT(*) as story_count') // Seleziona il creator_id e conta le storie
            ->groupBy('creator_id') // Raggruppa per creator_id
            ->orderByDesc('story_count') // Ordina per il numero di storie in modo decrescente
            ->limit(10) // Limita ai primi 10
            ->with('creator') // Precarica il creatore
            ->get()
            ->pluck('creator') // Ottiene solo i creatori
            ->unique('id'); // Rimuovi eventuali duplicati, se ce ne sono

    }
    private function getTags()
    {
        return Tag::withCount('tagged') // Conta quante storie sono associate a ciascun tag
            ->orderBy('tagged_count', 'desc') // Ordina per frequenza di utilizzo
            ->limit(10) // Limita ai primi 10 tag più usati
            ->get();
    }

    private function calculateRowData($year, $firstColumnCells, $thead, $nameFn, $queryFn, $quarter = null)
    {
        $rows = [];
        foreach ($firstColumnCells as $cell) {
            $row = [];
            foreach ($thead as $column) {
                if ($column === '') {
                    $row[] = $nameFn($cell, $column);
                } elseif ($column === 'totale') {
                    $totalPerUser = array_sum(array_slice($row, 1)); // Somma dei valori per gli stati
                    $row[] = $totalPerUser;
                } else {
                    $query = $queryFn($cell, $column);

                    if ($quarter) {
                        // Filtra per quarter se fornito
                        $query->whereRaw('EXTRACT(QUARTER FROM updated_at) = ?', [$quarter]);
                    }

                    if ($year !== 'All Time') {
                        $query->whereYear('updated_at', $year);
                    }

                    $statusTotal = $query->count();

                    // Aggiungi il totale per lo stato corrente
                    $row[] = $statusTotal;
                }
            }
            $rows[] = $row;
        }
        usort($rows, function ($a, $b) {
            return $b[count($a) - 1] <=> $a[count($a) - 1]; // Ordina in base all'ultima colonna (totale)
        });

        return $rows; // Restituisce un array di righe che segue l'ordine di thead
    }


    private function generateReportByUser($year, $availableQuarters, $developers)
    {
        $queryFn = function ($cell, $column) {
            return   Story::where('user_id', $cell->id)
                ->where('status', $column);
        };
        $nameFn = function ($cell) {
            return $cell->name;
        };
        $thead = array_merge([''], StoryStatus::values(), ['totale']);
        $firstColumnCells = $developers;

        return $this->generateQuarterReport($year, $availableQuarters, $firstColumnCells, $thead, $nameFn, $queryFn);
    }
    private function generateReportByCustomer($year, $availableQuarters, $customers)
    {
        $queryFn = function ($cell, $column) {
            return   Story::where('creator_id', $cell->id)
                ->where('status', $column);
        };
        $nameFn = function ($cell) {
            return $cell->name;
        };
        $thead = array_merge([''], StoryStatus::values(), ['totale']);
        $firstColumnCells = $customers;

        return $this->generateQuarterReport($year, $availableQuarters, $firstColumnCells, $thead, $nameFn, $queryFn);
    }
    private function generateReportByTag($year, $availableQuarters, $tags, $customers)
    {
        $queryFn = function ($tag, $column) {
            // Query per contare quante storie hanno il tag specificato e lo stato specificato
            return Story::whereHas('tags', function ($query) use ($tag) {
                $query->where('tags.id', $tag->id); // Filtra per il tag specifico
            })
                ->whereHas('creator', function ($q) use ($column) {
                    $q->where('name', $column); // Filtra per il nome dell'utente nel campo 'column'
                });
        };
        $nameFn = function ($cell) {
            return $cell->name;
        };
        $thead = array_merge([''], $customers->pluck('name')->toArray(), ['totale']);
        $firstColumnCells = $tags;

        return $this->generateQuarterReport($year, $availableQuarters, $firstColumnCells, $thead, $nameFn, $queryFn);
    }

    private function generateReportByStatusUser($year, $availableQuarters, $developers)
    {
        $thead = array_merge([''], $developers->pluck('name')->toArray(), ['totale']);
        $queryFn = function ($cell, $column) {
            return     Story::where('status', $cell)
                ->whereHas('user', function ($q) use ($column) {
                    $q->where('name', $column); // Filtra per il nome dell'utente nel campo 'column'
                });
        };
        $nameFn = function ($cell, $column) {
            return $cell ?? 'non assegnato';
        };
        $firstColumnCells = StoryStatus::values();

        return $this->generateQuarterReport($year, $availableQuarters, $firstColumnCells, $thead, $nameFn, $queryFn);
    }

    private function generateReportByStatusCustomer($year, $availableQuarters, $customer)
    {
        $thead = array_merge([''], $customer->pluck('name')->toArray(), ['totale']);
        $queryFn = function ($cell, $column) {
            return     Story::where('status', $cell)
                ->whereHas('creator', function ($q) use ($column) {
                    $q->where('name', $column); // Filtra per il nome dell'utente nel campo 'column'
                });
        };
        $nameFn = function ($cell, $column) {
            return $cell ?? 'non assegnato';
        };
        $firstColumnCells = StoryStatus::values();

        return $this->generateQuarterReport($year, $availableQuarters, $firstColumnCells, $thead, $nameFn, $queryFn);
    }
    private function generateReportByStatusTag($year, $availableQuarters, $tags, $customers)
    {
        $thead = array_merge([''], $tags->pluck('name')->toArray(), ['totale']);
        $queryFn = function ($cell, $column) use ($year) {
            return Story::whereNotNull('creator_id')
                ->whereHas('creator', function ($q) use ($cell, $column) {
                    $q->where('name', $cell->name); // Filtra per il nome dell'utente nel campo 'column'
                }) // Filtra per lo stato specifico
                ->whereHas('tags', function ($query) use ($cell, $column) {
                    $query->where('tags.name', $column); // Filtra per il nome del tag
                })
            ;
        };
        $nameFn = function ($cell, $column) {
            return $cell->name ?? 'non assegnato';
        };
        $firstColumnCells = $customers;

        return $this->generateQuarterReport($year, $availableQuarters, $firstColumnCells, $thead, $nameFn, $queryFn);
    }

    private function generateReportByTagType($year, $availableQuarters, $tags, $customers)
    {
        $thead = array_merge([''], StoryType::values(), ['totale']);
        $queryFn = function ($cell, $column) use ($year) {
            return Story::whereNotNull('creator_id')
                ->whereHas('tags', function ($query) use ($cell, $column) {
                    $query->where('tags.name', $cell->name); // Filtra per il nome del tag
                })
                ->where('type', $column);
        };
        $nameFn = function ($cell, $column) {
            return $cell->name ?? 'non assegnato';
        };
        $firstColumnCells = $tags;

        return $this->generateQuarterReport($year, $availableQuarters, $firstColumnCells, $thead, $nameFn, $queryFn);
    }




    private function generateQuarterReport($year, $availableQuarters, $firstColumnCells, $thead, $nameFn, $queryFn)
    {
        // Variabile per contenere i totali degli utenti e per l'intero anno
        $quarterReport = [];
        $quarterReport['thead'] = $thead;
        $quarterReport['tbody'] = [];

        $tbody['year'] = $this->calculateRowData($year, $firstColumnCells, $thead, $nameFn, $queryFn);
        foreach ($availableQuarters as $quarter) {
            $tbody['q' . $quarter] = $this->calculateRowData($year, $firstColumnCells, $thead, $nameFn, $queryFn, $quarter);
        }
        $quarterReport['tbody'] =   $tbody;

        return $quarterReport;
    }
}
