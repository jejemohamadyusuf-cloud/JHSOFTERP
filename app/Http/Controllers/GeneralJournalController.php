<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GeneralJournalRequest;
use App\Models\ChartOfAccount;
use App\Models\GeneralJournalEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

final class GeneralJournalController extends Controller
{
    public function index(): View
    {
        return view('general-journal.index', [
            'journals' => GeneralJournalEntry::query()
                ->with(['creator', 'cancellation'])
                ->withSum('lines as debit_total', 'debit_amount')
                ->withSum('lines as credit_total', 'credit_amount')
                ->latest('journal_date')
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('general-journal.create', [
            'accounts' => ChartOfAccount::query()
                ->where('is_active', true)
                ->where('is_header', false)
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function store(GeneralJournalRequest $request): RedirectResponse
    {
        $attributes = $request->validated();
        $journal = DB::transaction(function () use ($attributes): GeneralJournalEntry {
            $journal = GeneralJournalEntry::query()->create([
                'journal_number' => 'TMP-'.Str::uuid(),
                'journal_date' => $attributes['journal_date'],
                'reference' => $attributes['reference'] ?? null,
                'description' => $attributes['description'],
                'created_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            $journal->update([
                'journal_number' => sprintf('GJ-%s-%06d', $journal->journal_date->format('Ymd'), $journal->id),
            ]);

            foreach ($attributes['lines'] as $line) {
                $journal->lines()->create([
                    'chart_of_account_id' => $line['chart_of_account_id'],
                    'description' => $line['description'] ?? null,
                    'debit_amount' => $line['debit_amount'],
                    'credit_amount' => $line['credit_amount'],
                ]);
            }

            return $journal;
        });

        return Redirect::route('general-journal.show', $journal)
            ->with('status', 'General Journal berhasil diposting.');
    }

    public function show(GeneralJournalEntry $generalJournal): View
    {
        return view('general-journal.show', [
            'journal' => $generalJournal->load(['creator', 'lines.account']),
        ]);
    }
}
