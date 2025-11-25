<?php declare(strict_types=1);

namespace App\Domains\Geofence\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Domains\Geofence\Action\Store as StoreAction;
use App\Domains\CoreApp\Controller\ControllerWebAbstract;

class Store extends ControllerWebAbstract
{
    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:polygon,circle',
            'geometry' => 'required|json',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7',
        ]);

        try {
            $geofence = (new StoreAction())($request->all());
            
            return redirect()
                ->route('geofence.update', $geofence->id)
                ->with('success', __('Geofence created successfully'));
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('Error creating geofence: :message', ['message' => $e->getMessage()]));
        }
    }
}
