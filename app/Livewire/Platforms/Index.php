<?php

namespace App\Livewire\Platforms;

use App\Models\Platform;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $name = '';
    public string $platformId = '';
    public string $secret = '';
    public ?int $editId = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|min:2',
            'platformId' => 'required|min:2|unique:platforms,platform_id,' . $this->editId,
            'secret' => 'required|min:4',
        ];
    }

    public function generatePlatformId(): void
    {
        $this->platformId = Str::uuid()->toString();
    }

    public function generateSecret(): void
    {
        $this->secret = Str::random(32);
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editId) {
            Platform::where('id', $this->editId)->where('user_id', auth()->id())->update([
                'name' => $this->name,
                'platform_id' => $this->platformId,
                'secret' => $this->secret,
            ]);
        } else {
            Platform::create([
                'name' => $this->name,
                'platform_id' => $this->platformId,
                'secret' => $this->secret,
                'user_id' => auth()->id(),
            ]);
        }

        $this->reset(['name', 'platformId', 'secret', 'editId']);
    }

    public function edit(int $id): void
    {
        $platform = Platform::where('id', $id)->where('user_id', auth()->id())->firstOrFail();
        $this->editId = $platform->id;
        $this->name = $platform->name;
        $this->platformId = $platform->platform_id;
        $this->secret = $platform->secret;
    }

    public function delete(int $id): void
    {
        Platform::where('id', $id)->where('user_id', auth()->id())->delete();
    }

    public function render()
    {
        return view('livewire.platforms.index', [
            'platforms' => Platform::where('user_id', auth()->id())->get(),
        ]);
    }
}
