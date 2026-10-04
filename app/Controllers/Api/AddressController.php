<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Models\Address;

/**
 * /api/addresses — carnet d'adresses du client.
 */
final class AddressController extends ApiController
{
    /** GET /api/addresses */
    public function index(): never
    {
        $this->requireAuth();

        $addresses = Address::forUser((int) $this->userId());
        $this->ok(array_map(static fn (Address $address): array => $address->toApi(), $addresses));
    }

    /** GET /api/addresses/{id} */
    public function show(int $id): never
    {
        $this->requireAuth();

        $address = Address::findForUser($id, (int) $this->userId());
        if ($address === null) {
            $this->fail('Adresse introuvable.', 404);
        }

        $this->ok($address->toApi());
    }

    /** POST /api/addresses */
    public function store(): never
    {
        $this->requireAuth();

        $data = $this->validatePayload([
            'fullName' => 'required|max:160',
            'address'  => 'required|max:255',
            'city'     => 'required|max:120',
            'country'  => 'required|max:120',
            'phone'    => 'max:40',
        ]);

        $address = Address::createFor((int) $this->userId(), $data);
        $this->created($address->toApi(), 'Adresse enregistrée');
    }

    /** PUT /api/addresses/{id} */
    public function update(int $id): never
    {
        $this->requireAuth();

        $address = Address::updateFor($id, (int) $this->userId(), $this->payload());
        if ($address === null) {
            $this->fail('Adresse introuvable.', 404);
        }

        $this->ok($address->toApi(), 'Adresse mise à jour');
    }

    /** DELETE /api/addresses/{id} */
    public function destroy(int $id): never
    {
        $this->requireAuth();

        $address = Address::findForUser($id, (int) $this->userId());
        if ($address === null) {
            $this->fail('Adresse introuvable.', 404);
        }

        $address->delete();
        $this->ok(null, 'Adresse supprimée');
    }

    /** PUT /api/addresses/{id}/default */
    public function setDefault(int $id): never
    {
        $this->requireAuth();

        if (Address::findForUser($id, (int) $this->userId()) === null) {
            $this->fail('Adresse introuvable.', 404);
        }

        Address::setDefault($id, (int) $this->userId());
        $this->ok(['id' => $id, 'isDefault' => true], 'Adresse par défaut mise à jour');
    }
}