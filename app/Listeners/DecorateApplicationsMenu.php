<?php

namespace App\Listeners;

use App\Models\ServiceRequest;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;
use ReflectionClass;
use Throwable;

class DecorateApplicationsMenu
{
    public function handle(BuildingMenu $event): void
    {
        $user = Auth::user();
        if (!$user || !method_exists($user, 'isAdminOrApplication') || !$user->isAdminOrApplication()) {
            return;
        }

        try {
            $counts = [
                'apps_new_entries' => Student::query()
                    ->where(function ($q) {
                        $q->whereNull('application_status')
                          ->orWhere('application_status', 'new_dispatch');
                    })
                    ->count(),
                'apps_doc_requests' => ServiceRequest::where('type', 'documentation')
                    ->whereNotIn('status', ['completed'])
                    ->count(),
                'apps_refunds' => ServiceRequest::where('type', 'refund')
                    ->whereNotIn('status', ['completed', 'rejected'])
                    ->count(),
                'apps_cancellations' => ServiceRequest::where('type', 'cancellation')
                    ->whereNotIn('status', ['completed'])
                    ->count(),
                'apps_special_approvals' => Student::query()
                    ->where(function ($q) {
                        $q->where('special_condition_status', 'pending')
                          ->orWhere('reduced_entry_status', 'pending');
                    })
                    ->count(),
            ];

            $builder = $event->menu;
            $ref = new ReflectionClass($builder);

            $rawProp = $ref->getProperty('rawMenu');
            $rawProp->setAccessible(true);
            $rawMenu = $rawProp->getValue($builder);

            foreach ($rawMenu as $i => $item) {
                if (!is_array($item) || !isset($item['key'])) {
                    continue;
                }
                if (!array_key_exists($item['key'], $counts)) {
                    continue;
                }

                $count = $counts[$item['key']];
                if ($count > 0) {
                    $item['label'] = (string) $count;
                    $item['label_color'] = 'danger';
                } else {
                    unset($item['label'], $item['label_color']);
                }
                $rawMenu[$i] = $item;
            }

            $rawProp->setValue($builder, $rawMenu);

            $compileProp = $ref->getProperty('shouldCompile');
            $compileProp->setAccessible(true);
            $compileProp->setValue($builder, true);
        } catch (Throwable $e) {
            Log::warning('DecorateApplicationsMenu failed; sidebar will render without badges.', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
