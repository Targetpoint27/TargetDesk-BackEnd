<?php

namespace App\Observers;

use App\Models\Contact;
use Illuminate\Support\Facades\Log;

class ContactObserver
{
    /**
     * Handle the Contact "creating" event.
     *
     * @param  \App\Models\Contact  $contact
     * @return void
     */
    public function creating(Contact $contact)
    {
        // Vérifier s'il faut définir comme contact principal automatiquement
        $existingPrimaryContact = null;

        if ($contact->client_id) {
            $existingPrimaryContact = Contact::forClient($contact->client_id)
                ->primary()
                ->active()
                ->first();
        } elseif ($contact->supplier_id) {
            $existingPrimaryContact = Contact::forSupplier($contact->supplier_id)
                ->primary()
                ->active()
                ->first();
        }

        // Si aucun contact principal existe et que ce contact est marqué comme principal
        // ou s'il n'y a pas de contact principal du tout
        if (!$existingPrimaryContact) {
            $contact->is_primary = true;
        }

        // Si ce contact est marqué comme principal mais qu'il y en a déjà un
        if ($contact->is_primary && $existingPrimaryContact && $existingPrimaryContact->id !== $contact->id) {
            // Désactiver l'ancien contact principal
            $existingPrimaryContact->update(['is_primary' => false]);

            Log::info('Contact principal changé lors de création', [
                'old_primary_contact_id' => $existingPrimaryContact->id,
                'new_primary_contact_id' => $contact->id,
                'client_id' => $contact->client_id,
                'supplier_id' => $contact->supplier_id
            ]);
        }
    }

    /**
     * Handle the Contact "created" event.
     *
     * @param  \App\Models\Contact  $contact
     * @return void
     */
    public function created(Contact $contact)
    {
        Log::info('Contact créé', [
            'contact_id' => $contact->id,
            'client_id' => $contact->client_id,
            'supplier_id' => $contact->supplier_id,
            'full_name' => $contact->full_name,
            'is_primary' => $contact->is_primary,
            'created_by' => $contact->created_by
        ]);
    }

    /**
     * Handle the Contact "updating" event.
     *
     * @param  \App\Models\Contact  $contact
     * @return void
     */
    public function updating(Contact $contact)
    {
        // Si le statut principal change
        if ($contact->isDirty('is_primary') && $contact->is_primary) {
            if ($contact->client_id) {
                // Désactiver les autres contacts principaux du même client
                Contact::forClient($contact->client_id)
                    ->primary()
                    ->where('id', '!=', $contact->id)
                    ->update(['is_primary' => false]);
            } elseif ($contact->supplier_id) {
                // Désactiver les autres contacts principaux du même fournisseur
                Contact::forSupplier($contact->supplier_id)
                    ->primary()
                    ->where('id', '!=', $contact->id)
                    ->update(['is_primary' => false]);
            }

            Log::info('Contact défini comme principal', [
                'contact_id' => $contact->id,
                'client_id' => $contact->client_id,
                'supplier_id' => $contact->supplier_id,
                'full_name' => $contact->full_name
            ]);
        }
    }

    /**
     * Handle the Contact "updated" event.
     *
     * @param  \App\Models\Contact  $contact
     * @return void
     */
    public function updated(Contact $contact)
    {
        $changes = $contact->getChanges();

        if (!empty($changes)) {
            Log::info('Contact mis à jour', [
                'contact_id' => $contact->id,
                'client_id' => $contact->client_id,
                'supplier_id' => $contact->supplier_id,
                'full_name' => $contact->full_name,
                'changes' => $changes
            ]);
        }
    }

    /**
     * Handle the Contact "deleting" event.
     *
     * @param  \App\Models\Contact  $contact
     * @return void
     */
    public function deleting(Contact $contact)
    {
        // Si on supprime le contact principal, vérifier s'il faut en promouvoir un autre
        if ($contact->is_primary && $contact->is_active) {
            $nextContact = null;

            if ($contact->client_id) {
                $nextContact = Contact::forClient($contact->client_id)
                    ->active()
                    ->where('id', '!=', $contact->id)
                    ->orderBy('created_at')
                    ->first();
            } elseif ($contact->supplier_id) {
                $nextContact = Contact::forSupplier($contact->supplier_id)
                    ->active()
                    ->where('id', '!=', $contact->id)
                    ->orderBy('created_at')
                    ->first();
            }

            if ($nextContact) {
                $nextContact->update(['is_primary' => true]);

                Log::info('Nouveau contact principal auto-désigné', [
                    'old_primary_contact_id' => $contact->id,
                    'new_primary_contact_id' => $nextContact->id,
                    'client_id' => $contact->client_id,
                    'supplier_id' => $contact->supplier_id
                ]);
            }
        }
    }

    /**
     * Handle the Contact "deleted" event.
     *
     * @param  \App\Models\Contact  $contact
     * @return void
     */
    public function deleted(Contact $contact)
    {
        Log::info('Contact supprimé', [
            'contact_id' => $contact->id,
            'client_id' => $contact->client_id,
            'supplier_id' => $contact->supplier_id,
            'full_name' => $contact->full_name,
            'was_primary' => $contact->is_primary
        ]);
    }

    /**
     * Handle the Contact "restored" event.
     *
     * @param  \App\Models\Contact  $contact
     * @return void
     */
    public function restored(Contact $contact)
    {
        Log::info('Contact restauré', [
            'contact_id' => $contact->id,
            'client_id' => $contact->client_id,
            'supplier_id' => $contact->supplier_id,
            'full_name' => $contact->full_name
        ]);
    }

    /**
     * Handle the Contact "force deleted" event.
     *
     * @param  \App\Models\Contact  $contact
     * @return void
     */
    public function forceDeleted(Contact $contact)
    {
        Log::warning('Contact définitivement supprimé', [
            'contact_id' => $contact->id,
            'client_id' => $contact->client_id,
            'supplier_id' => $contact->supplier_id,
            'full_name' => $contact->full_name
        ]);
    }
}
