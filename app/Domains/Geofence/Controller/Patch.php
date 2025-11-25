<?php declare(strict_types=1);

namespace App\Domains\Geofence\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Domains\Geofence\Model\Geofence as GeofenceModel;
use App\Domains\Geofence\Action\Update as UpdateAction;
use App\Domains\CoreApp\Controller\ControllerWebAbstract;

class Patch extends ControllerWebAbstract
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param int $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $geofence = GeofenceModel::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'geometry' => 'required|json',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        try {
            (new UpdateAction())($geofence, $request->all());
            
            return redirect()
                ->route('geofence.update', $geofence->id)
                ->with('success', __('Geofence updated successfully'));
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('Error updating geofence: :message', ['message' => $e->getMessage()]));
        }
    }
}
