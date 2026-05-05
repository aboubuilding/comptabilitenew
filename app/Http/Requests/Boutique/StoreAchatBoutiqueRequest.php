namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAchatBoutiqueRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'date_achat'         => 'required|date',
            'fournisseur_id'     => 'nullable|exists:fournisseurs,id',
            'reference'          => 'nullable|string|max:255',
            'commentaire'        => 'nullable|string',
            'statut_paiement'    => 'nullable|in:0,1,2',
            'statut_livraison'   => 'nullable|in:0,1,2',
            'details'            => 'required|array|min:1',
            'details.*.produit_id' => 'required|exists:produits,id',
            'details.*.quantite'   => 'required|numeric|min:0.001',
            'details.*.unite'      => 'required|string',
            'details.*.prix_unitaire' => 'required|numeric|min:0',
        ];
    }
}