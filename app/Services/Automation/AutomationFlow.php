<?php
namespace App\Services\Automation;

use App\Core\App;
use App\Core\Logger;

/**
 * Executes a *config-driven* browser flow. The steps (URLs + selectors) live in
 * settings/data — NOT hardcoded — because each OTA extranet has its own login
 * form and DOM that changes over time. An operator records the selectors once
 * (via the browser dev tools) and the flow runs them headless in the queue.
 *
 * Supported step actions:
 *   navigate      {url}
 *   type          {selector, value}
 *   click         {selector}
 *   waitFor       {selector, timeout?}
 *   selectOption  {selector, value}          (sets <select> via JS)
 *   assertUrlContains {value}
 *   assertText    {selector, contains}
 *   extract       {selector, as}             (store element text in results)
 *   extractAll    {selector, as}             (store list of texts)
 *   script        {js, as?}
 *   screenshot    {name}                      (saved to storage/cache)
 *   sleep         {ms}
 *
 * Values support {{placeholders}} resolved from the flow context
 * (credentials + runtime params like dates/rates).
 */
class AutomationFlow
{
    private BrowserDriver $driver;
    private array $context;
    private array $results = [];
    private array $screenshots = [];

    public function __construct(BrowserDriver $driver, array $context = [])
    {
        $this->driver = $driver;
        $this->context = $context;
    }

    /**
     * Run a list of steps. Returns:
     *   ['success'=>bool,'results'=>[...],'screenshots'=>[...],'error'=>?string,
     *    'failed_step'=>?int]
     */
    public function run(array $steps): array
    {
        $ownDriver = false;
        try {
            if (!$this->driver->isStarted()) {
                $this->driver->start();
                $ownDriver = true;
            }
            foreach ($steps as $i => $step) {
                $this->execute($step);
            }
            $result = ['success' => true, 'results' => $this->results, 'screenshots' => $this->screenshots, 'error' => null];
        } catch (\Throwable $e) {
            Logger::warn('AutomationFlow failed', ['error' => $e->getMessage()]);
            // Capture a failure screenshot for debugging when possible.
            $shot = $this->safeScreenshot('error');
            $result = [
                'success' => false,
                'results' => $this->results,
                'screenshots' => $this->screenshots,
                'error' => $e->getMessage(),
                'error_screenshot' => $shot,
            ];
        } finally {
            if ($ownDriver) {
                $this->driver->quit();
            }
        }
        return $result;
    }

    private function execute(array $step): void
    {
        $action = $step['action'] ?? '';
        $sel = isset($step['selector']) ? $this->interp($step['selector']) : null;

        switch ($action) {
            case 'navigate':
                $this->driver->navigate($this->interp($step['url']));
                break;
            case 'type':
                $this->driver->type($sel, $this->interp($step['value'] ?? ''));
                break;
            case 'click':
                $this->driver->click($sel);
                break;
            case 'waitFor':
                if ($this->driver->waitFor($sel, (int) ($step['timeout'] ?? 15000)) === null) {
                    throw new \RuntimeException("waitFor timed out: $sel");
                }
                break;
            case 'selectOption':
                $val = $this->interp($step['value'] ?? '');
                $this->driver->script(
                    "var el=document.querySelector(arguments[0]); if(el){el.value=arguments[1];el.dispatchEvent(new Event('change',{bubbles:true}));}",
                    [$sel, $val]
                );
                break;
            case 'assertUrlContains':
                $needle = $this->interp($step['value']);
                if (!str_contains($this->driver->currentUrl(), $needle)) {
                    throw new \RuntimeException("URL does not contain '$needle' (login may have failed).");
                }
                break;
            case 'assertText':
                $text = $this->driver->text($sel) ?? '';
                $needle = $this->interp($step['contains'] ?? '');
                if (!str_contains($text, $needle)) {
                    throw new \RuntimeException("Element '$sel' does not contain '$needle'.");
                }
                break;
            case 'extract':
                $this->results[$step['as'] ?? 'value'] = $this->driver->text($sel);
                break;
            case 'extractAll':
                $this->results[$step['as'] ?? 'items'] = $this->driver->script(
                    "return Array.from(document.querySelectorAll(arguments[0])).map(function(e){return e.innerText;});",
                    [$sel]
                );
                break;
            case 'script':
                $out = $this->driver->script($this->interp($step['js']));
                if (!empty($step['as'])) {
                    $this->results[$step['as']] = $out;
                }
                break;
            case 'screenshot':
                $path = $this->safeScreenshot($step['name'] ?? 'shot');
                if ($path) {
                    $this->screenshots[] = $path;
                }
                break;
            case 'sleep':
                usleep((int) ($step['ms'] ?? 500) * 1000);
                break;
            default:
                throw new \RuntimeException("Unknown flow action: $action");
        }
    }

    /** Interpolate {{placeholders}} from the context. */
    private function interp(string $value): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', function ($m) {
            return (string) ($this->context[$m[1]] ?? '');
        }, $value);
    }

    private function safeScreenshot(string $name): ?string
    {
        try {
            $dir = App::config('paths.cache', sys_get_temp_dir());
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $path = $dir . '/flow-' . preg_replace('/\W+/', '_', $name) . '-' . date('His') . '.png';
            return $this->driver->saveScreenshot($path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function results(): array { return $this->results; }
    public function screenshots(): array { return $this->screenshots; }
}
