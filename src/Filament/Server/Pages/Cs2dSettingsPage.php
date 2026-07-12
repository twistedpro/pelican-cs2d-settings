<?php

namespace JasonDyer\Cs2dSettings\Filament\Server\Pages;

use BackedEnum;
use UnitEnum;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use JasonDyer\Cs2dSettings\Services\Cs2dServerDetector;
use Throwable;

class Cs2dSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'tabler-settings';

    protected static ?string $navigationLabel = 'CS2D Settings';

    protected static string|UnitEnum|null $navigationGroup = 'CS2D';

    protected static ?string $title = 'CS2D Settings';

    protected string $view = 'cs2dsettings::filament.server.pages.cs2d-settings-page';

    public array $availableMaps = [
        'de_dust2' => 'de_dust2',
    ];

    public array $formData = [
        'server_name' => 'CS2D Server',
        'server_password' => '',
        'rcon_password' => 'changeme',

        'start_map' => 'de_dust2',

        'sv_gamemode' => 0,
        'sv_specmode' => 1,
        'sv_maxplayers' => 12,

        'mp_recoil' => 0,
        'mp_timelimit' => 0,
        'mp_winlimit' => 0,
        'mp_roundlimit' => 0,
        'mp_roundtime' => 5,
        'mp_freezetime' => 0,
        'mp_buytime' => '0.5',
        'mp_startmoney' => 16000,
        'mp_teamkillpenalty' => 3,
        'mp_hostagepenalty' => 8,
        'mp_tkpunish' => 1,
        'mp_idlekick' => 1,
        'mp_vulnerablehostages' => 1,
        'mp_autoteambalance' => 1,
        'mp_spectatemouse' => 1,

        'bot_autofill' => 1,
        'bot_count' => 4,
        'bot_jointeam' => 0,
        'bot_prefix' => '[b]',
    ];

    public static function canAccess(): bool
    {
        $server = Filament::getTenant();

        return parent::canAccess()
            && $server !== null
            && Cs2dServerDetector::isCs2dServer($server);
    }

    public function getTitle(): string
    {
        return 'CS2D Settings';
    }

    protected function getFormStatePath(): ?string
    {
        return 'formData';
    }

    public function mount(): void
    {
        $server = Filament::getTenant();

        try {
            $this->availableMaps = $this->listMaps($server);

            if ($this->fileExists($server, 'sys/server.cfg')) {
                $contents = $this->readFile($server, 'sys/server.cfg');
                $this->formData = array_merge($this->formData, $this->parseConfig($contents));
            }

            if (! isset($this->availableMaps[$this->formData['start_map']])) {
                $this->availableMaps[$this->formData['start_map']] = $this->formData['start_map'];
            }
        } catch (Exception $exception) {
            report($exception);

            Notification::make()
                ->title('Could not fully load CS2D settings')
                ->body('The settings page loaded, but maps or sys/server.cfg could not be read yet.')
                ->warning()
                ->send();
        }

        $this->form->fill($this->formData);
    }

    public function getFormSchema(): array
    {
        return [
            Tabs::make('CS2D Settings')
                ->tabs([
                    Tab::make('Server')
                        ->schema([
                            Section::make('Server')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('server_name')
                                        ->label('Server Name')
                                        ->required()
                                        ->maxLength(64),

                                    Select::make('start_map')
                                        ->label('Start Map')
                                        ->options(fn (): array => $this->availableMaps)
                                        ->searchable()
                                        ->native(false)
                                        ->required(),

                                    TextInput::make('server_password')
                                        ->label('Server Password')
                                        ->maxLength(64),

                                    TextInput::make('rcon_password')
                                        ->label('RCON Password')
                                        ->password()
                                        ->revealable()
                                        ->required()
                                        ->maxLength(64),
                                ]),

                            Section::make('Server Rules')
                                ->columns(4)
                                ->schema([
                                    Select::make('sv_gamemode')
                                        ->label('Game Mode')
                                        ->options([
                                            0 => 'Standard',
                                            1 => 'Deathmatch',
                                            2 => 'Team Deathmatch',
                                            3 => 'Construction',
                                            4 => 'Zombies',
                                        ])
                                        ->native(false)
                                        ->required(),

                                    Select::make('sv_specmode')
                                        ->label('Spectator Mode')
                                        ->options([
                                            0 => 'Disabled',
                                            1 => 'Enabled',
                                            2 => 'Advanced',
                                        ])
                                        ->native(false)
                                        ->required(),

                                    TextInput::make('sv_maxplayers')
                                        ->label('Max Players')
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(32)
                                        ->required(),

                                    Select::make('mp_recoil')
                                        ->label('Weapon Recoil')
                                        ->options([
                                            0 => 'Off',
                                            1 => 'On',
                                        ])
                                        ->native(false)
                                        ->required(),
                                ]),
                        ]),

                    Tab::make('Gameplay')
                        ->schema([
                            Section::make('Match Limits')
                                ->columns(3)
                                ->schema([
                                    TextInput::make('mp_timelimit')
                                        ->label('Time Limit')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(999)
                                        ->required(),

                                    TextInput::make('mp_winlimit')
                                        ->label('Win Limit')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(999)
                                        ->required(),

                                    TextInput::make('mp_roundlimit')
                                        ->label('Round Limit')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(999)
                                        ->required(),

                                    TextInput::make('mp_roundtime')
                                        ->label('Round Time')
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(60)
                                        ->required(),

                                    TextInput::make('mp_freezetime')
                                        ->label('Freeze Time')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(60)
                                        ->required(),

                                    TextInput::make('mp_buytime')
                                        ->label('Buy Time')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(60)
                                        ->required(),
                                ]),

                            Section::make('Gameplay')
                                ->columns(3)
                                ->schema([
                                    TextInput::make('mp_startmoney')
                                        ->label('Start Money')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(16000)
                                        ->required(),

                                    TextInput::make('mp_teamkillpenalty')
                                        ->label('Team Kill Penalty')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(99)
                                        ->required(),

                                    TextInput::make('mp_hostagepenalty')
                                        ->label('Hostage Penalty')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(99)
                                        ->required(),

                                    Select::make('mp_tkpunish')
                                        ->label('TK Punish')
                                        ->options([
                                            0 => 'Off',
                                            1 => 'On',
                                        ])
                                        ->native(false)
                                        ->required(),

                                    Select::make('mp_idlekick')
                                        ->label('Idle Kick')
                                        ->options([
                                            0 => 'Off',
                                            1 => 'On',
                                        ])
                                        ->native(false)
                                        ->required(),

                                    Select::make('mp_vulnerablehostages')
                                        ->label('Vulnerable Hostages')
                                        ->options([
                                            0 => 'Off',
                                            1 => 'On',
                                        ])
                                        ->native(false)
                                        ->required(),

                                    Select::make('mp_autoteambalance')
                                        ->label('Auto Team Balance')
                                        ->options([
                                            0 => 'Off',
                                            1 => 'On',
                                        ])
                                        ->native(false)
                                        ->required(),

                                    Select::make('mp_spectatemouse')
                                        ->label('Spectate Mouse')
                                        ->options([
                                            0 => 'Off',
                                            1 => 'On',
                                        ])
                                        ->native(false)
                                        ->required(),
                                ]),
                        ]),

                    Tab::make('Bots')
                        ->schema([
                            Section::make('Bots')
                                ->columns(4)
                                ->schema([
                                    Toggle::make('bot_autofill')
                                        ->label('Bot Autofill')
                                        ->helperText('When enabled, bots fill empty player slots and leave as humans join.')
                                        ->live(),

                                    TextInput::make('bot_count')
                                        ->label('Bot Target Count')
                                        ->helperText('Only used when Bot Autofill is disabled.')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(32)
                                        ->disabled(fn (callable $get): bool => (bool) $get('bot_autofill'))
                                        ->dehydrated()
                                        ->required(),

                                    Select::make('bot_jointeam')
                                        ->label('Bot Join Team')
                                        ->options([
                                            0 => 'Both Teams',
                                            1 => 'Terrorists',
                                            2 => 'Counter-Terrorists',
                                        ])
                                        ->native(false)
                                        ->required(),

                                    TextInput::make('bot_prefix')
                                        ->label('Bot Prefix')
                                        ->maxLength(16)
                                        ->required(),
                                ]),
                        ]),
                ]),
        ];
    }


    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save Settings')
                ->icon('tabler-device-floppy')
                ->color('primary')
                ->action('saveSettings'),

            \Filament\Actions\Action::make('restart')
                ->label('Restart Server')
                ->icon('tabler-refresh')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Restart CS2D server?')
                ->modalDescription('The server will be restarted so CS2D can load the updated settings.')
                ->modalSubmitActionLabel('Restart now')
                ->action('restartServer'),
        ];
    }

    public function saveSettings(): void
    {
        $server = Filament::getTenant();
        $data = $this->form->getState();

        $startMap = $this->cleanMapName((string) $data['start_map']);

        $config = implode("\n", [
            '// Managed by Pelican CS2D Settings Plugin',

            'sv_name "' . $this->escapeCfg((string) $data['server_name']) . '"',
            'sv_password "' . $this->escapeCfg((string) ($data['server_password'] ?? '')) . '"',
            'sv_rcon "' . $this->escapeCfg((string) $data['rcon_password']) . '"',

            'sv_gamemode ' . (int) $data['sv_gamemode'],
            'sv_specmode ' . (int) $data['sv_specmode'],
            'sv_maxplayers ' . (int) $data['sv_maxplayers'],

            'mp_recoil ' . (int) $data['mp_recoil'],
            'mp_timelimit ' . (int) $data['mp_timelimit'],
            'mp_winlimit ' . (int) $data['mp_winlimit'],
            'mp_roundlimit ' . (int) $data['mp_roundlimit'],
            'mp_roundtime ' . (int) $data['mp_roundtime'],
            'mp_freezetime ' . (int) $data['mp_freezetime'],
            'mp_buytime ' . $this->cleanDecimal((string) $data['mp_buytime']),
            'mp_startmoney ' . (int) $data['mp_startmoney'],
            'mp_teamkillpenalty ' . (int) $data['mp_teamkillpenalty'],
            'mp_hostagepenalty ' . (int) $data['mp_hostagepenalty'],
            'mp_tkpunish ' . (int) $data['mp_tkpunish'],
            'mp_idlekick ' . (int) $data['mp_idlekick'],
            'mp_vulnerablehostages ' . (int) $data['mp_vulnerablehostages'],
            'mp_autoteambalance ' . (int) $data['mp_autoteambalance'],
            'mp_spectatemouse ' . (int) $data['mp_spectatemouse'],

            'bot_autofill ' . ((bool) $data['bot_autofill'] ? 1 : 0),
            'bot_jointeam ' . (int) $data['bot_jointeam'],
            'bot_prefix "' . $this->escapeCfg((string) $data['bot_prefix']) . '"',
            'bot_count ' . (int) $data['bot_count'],

            'map "' . $this->escapeCfg($startMap) . '"',

            '',
        ]);

        try {
            // CS2D reliably applies settings when they are directly in sys/server.cfg.
            // Replace our managed block when present. Otherwise migrate old scattered keys once.
            $serverCfg = $this->fileExists($server, 'sys/server.cfg')
                ? $this->readFile($server, 'sys/server.cfg')
                : '';

            // Remove old include line if we previously added it.
            $serverCfg = preg_replace('/^\s*exec\s+"sys\/pelican-generated\.cfg"\s*$/m', '', $serverCfg);
            $serverCfg = preg_replace('/^\s*exec\s+"pelican-generated\.cfg"\s*$/m', '', $serverCfg);
            $settings = $this->configToKeyValueLines($config);
            $managedBlock = implode("\n", [
                '// --- Pelican CS2D Settings START ---',
                '// Managed by Pelican CS2D Settings Plugin',
                ...array_values($settings),
                '// --- Pelican CS2D Settings END ---',
            ]);

            $managedBlockPattern = '/\/\/ --- Pelican CS2D Settings START ---.*?\/\/ --- Pelican CS2D Settings END ---/s';

            if (preg_match($managedBlockPattern, $serverCfg)) {
                $serverCfg = preg_replace($managedBlockPattern, $managedBlock, $serverCfg, 1);
            } else {
                $serverCfg = preg_replace('/^\s*\/\/ Managed by Pelican CS2D Settings Plugin\s*(?:\R|$)/m', '', $serverCfg);

                foreach (array_keys($settings) as $key) {
                    $serverCfg = preg_replace('/^\s*' . preg_quote($key, '/') . '\s+.*(?:\R|$)/m', '', $serverCfg);
                }

                $serverCfg = rtrim($serverCfg) . "\n\n" . $managedBlock . "\n";
            }

            $this->writeFile($server, 'sys/server.cfg', trim($serverCfg) . "\n");
            $syncedServerPlayers = $this->syncServerPlayersStartupVariable($server, (int) $data['sv_maxplayers']);

            Notification::make()
                ->title('CS2D settings saved')
                ->body($syncedServerPlayers
                    ? 'sys/server.cfg and the SERVER_PLAYERS startup variable were updated. Restart the server for changes to take effect.'
                    : 'sys/server.cfg was updated. Restart the server for changes to take effect.')
                ->success()
                ->send();
        } catch (Exception $exception) {
            report($exception);

            Notification::make()
                ->title('Save failed')
                ->body('Could not write the CS2D config through the daemon file API.')
                ->danger()
                ->send();
        }
    }

    public function restartServer(): void
    {
        $server = Filament::getTenant();

        try {
            Http::daemon($server->node)
                ->post("/api/servers/{$server->uuid}/power", [
                    'action' => 'restart',
                ])
                ->throw();

            Notification::make()
                ->title('Restart requested')
                ->body('The CS2D server is restarting so the new settings can take effect.')
                ->success()
                ->send();
        } catch (Exception $exception) {
            report($exception);

            Notification::make()
                ->title('Restart failed')
                ->body('Could not send the restart command to Wings.')
                ->danger()
                ->send();
        }
    }

    private function parseConfig(string $contents): array
    {
        $data = [];

        foreach (explode("\n", $contents) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '//')) {
                continue;
            }

            $patterns = [
                'server_name' => '/^sv_name\s+"?(.*?)"?$/',
                'server_password' => '/^sv_password\s+"?(.*?)"?$/',
                'rcon_password' => '/^sv_rcon\s+"?(.*?)"?$/',
                'start_map' => '/^map\s+"?(.*?)"?$/',
                'bot_prefix' => '/^bot_prefix\s+"?(.*?)"?$/',
            ];

            foreach ($patterns as $key => $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $data[$key] = $this->unescapeCfg(trim($matches[1], '"'));
                    continue 2;
                }
            }

            $numericPatterns = [
                'sv_gamemode',
                'sv_specmode',
                'sv_maxplayers',
                'mp_recoil',
                'mp_timelimit',
                'mp_winlimit',
                'mp_roundlimit',
                'mp_roundtime',
                'mp_freezetime',
                'mp_buytime',
                'mp_startmoney',
                'mp_teamkillpenalty',
                'mp_hostagepenalty',
                'mp_tkpunish',
                'mp_idlekick',
                'mp_vulnerablehostages',
                'mp_autoteambalance',
                'mp_spectatemouse',
                'bot_autofill',
                'bot_count',
                'bot_jointeam',
            ];

            foreach ($numericPatterns as $key) {
                if (preg_match('/^' . preg_quote($key, '/') . '\s+([0-9.]+)/', $line, $matches)) {
                    if ($key === 'bot_autofill') {
                        $data[$key] = ((int) $matches[1]) === 1;
                    } else {
                        $data[$key] = str_contains($matches[1], '.') ? $matches[1] : (int) $matches[1];
                    }
                    continue 2;
                }
            }
        }

        return $data;
    }

    private function listMaps(mixed $server): array
    {
        $maps = [];

        $response = Http::daemon($server->node)
            ->get("/api/servers/{$server->uuid}/files/list-directory", [
                'directory' => 'maps',
            ]);

        if (! $response->successful()) {
            $response = Http::daemon($server->node)
                ->get("/api/servers/{$server->uuid}/files/list-directory", [
                    'directory' => '/maps',
                ]);
        }

        if (! $response->successful()) {
            return [
                'de_dust2' => 'de_dust2',
            ];
        }

        $json = $response->json();
        $items = $json['data'] ?? $json ?? [];

        foreach ($items as $item) {
            $attributes = $item['attributes'] ?? $item;

            $name = $attributes['name'] ?? null;
            $isFile = $attributes['is_file'] ?? $attributes['isFile'] ?? null;

            if (! is_string($name)) {
                continue;
            }

            if ($isFile === false) {
                continue;
            }

            if (! str_ends_with(strtolower($name), '.map')) {
                continue;
            }

            $mapName = preg_replace('/\.map$/i', '', $name);

            if ($mapName !== '') {
                $maps[$mapName] = $mapName;
            }
        }

        if ($maps === []) {
            return [
                'de_dust2' => 'de_dust2',
            ];
        }

        ksort($maps);

        return $maps;
    }

    private function escapeCfg(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\"'], $value);
    }

    private function unescapeCfg(string $value): string
    {
        $result = '';
        $length = strlen($value);

        for ($index = 0; $index < $length; $index++) {
            $character = $value[$index];

            if ($character === '\\' && $index + 1 < $length && in_array($value[$index + 1], ['\\', '"'], true)) {
                $result .= $value[$index + 1];
                $index++;
                continue;
            }

            $result .= $character;
        }

        return $result;
    }

    private function cleanMapName(string $value): string
    {
        $value = preg_replace('/\.map$/i', '', $value);
        $value = preg_replace('/[^a-zA-Z0-9_\-]/', '', $value);

        return $value ?: 'de_dust2';
    }

    private function cleanDecimal(string $value): string
    {
        $value = preg_replace('/[^0-9.]/', '', $value);

        if ($value === '' || ! is_numeric($value)) {
            return '0';
        }

        return (string) $value;
    }

    private function configToKeyValueLines(string $config): array
    {
        $settings = [];

        foreach (explode("\n", $config) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '//')) {
                continue;
            }

            $parts = preg_split('/\s+/', $line, 2);

            if (! isset($parts[0], $parts[1])) {
                continue;
            }

            $settings[$parts[0]] = $line;
        }

        return $settings;
    }

    private function syncServerPlayersStartupVariable(mixed $server, int $maxPlayers): bool
    {
        try {
            $serverId = $server->id ?? null;
            $eggId = $server->egg_id ?? null;

            if ($serverId === null || $eggId === null) {
                return false;
            }

            $variableId = DB::table('egg_variables')
                ->where('egg_id', $eggId)
                ->where('env_variable', 'SERVER_PLAYERS')
                ->value('id');

            if ($variableId === null) {
                return false;
            }

            DB::table('server_variables')->updateOrInsert(
                [
                    'server_id' => $serverId,
                    'variable_id' => $variableId,
                ],
                [
                    'variable_value' => (string) $maxPlayers,
                ],
            );

            return true;
        } catch (Throwable $throwable) {
            report($throwable);

            return false;
        }
    }

    private function fileExists(mixed $server, string $path): bool
    {
        $response = Http::daemon($server->node)
            ->get("/api/servers/{$server->uuid}/files/contents", [
                'file' => $path,
            ]);

        return $response->successful();
    }

    private function readFile(mixed $server, string $path): string
    {
        return Http::daemon($server->node)
            ->get("/api/servers/{$server->uuid}/files/contents", [
                'file' => $path,
            ])
            ->throw()
            ->body();
    }

    private function writeFile(mixed $server, string $path, string $contents): void
    {
        $response = Http::daemon($server->node)
            ->withBody($contents, 'text/plain')
            ->post("/api/servers/{$server->uuid}/files/write?file=" . rawurlencode($path));

        if (! $response->successful()) {
            throw new Exception('Daemon write failed: HTTP ' . $response->status() . ' - ' . $response->body());
        }
    }
}
