<?php

namespace Nabcellent\Laraconfig;

use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Psr\SimpleCache\InvalidArgumentException;
use Serializable;

class SettingsCache implements Serializable
{
    /**
     * The collection of settings to persist.
     *
     * @var SettingsCollection|null
     */
    protected ?SettingsCollection $settings = null;

    /**
     * If the cache was already invalidated (to not do it again).
     *
     * @var Carbon|null
     */
    protected ?Carbon $invalidatedAt = null;

    /**
     * SettingsCache constructor.
     *
     * @param Repository $cache
     * @param  string    $key
     * @param  int       $ttl
     * @param  bool      $automaticRegeneration
     */
    public function __construct(
        protected Repository $cache,
        protected string $key,
        protected int $ttl,
        protected bool $automaticRegeneration = false
    ) {
    }

    /**
     * Set the settings collection to persist.
     *
     * @param SettingsCollection $settings
     *
     * @return SettingsCache
     */
    public function setSettings(SettingsCollection $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Returns the collection in the cache, if it exists.
     *
     * @return Collection|null
     * @throws InvalidArgumentException
     */
    public function retrieve(): ?Collection
    {
        return $this->cache->get($this->key);
    }

    /**
     * Check if the cache of the settings not is older these settings.
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function shouldRegenerate(): bool
    {
        // If the time doesn't exist in the cache then we can safely store.
        if (!($time = $this->cache->get("$this->key:time"))) {
            return true;
        }

        // Return if the is an invalidation (data changed) and is fresher.
        return (bool) $this->invalidatedAt?->isAfter($time);
    }

    /**
     * Saves the collection of settings in the cache.
     *
     * @param bool $force
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function regenerate(bool $force = false): void
    {
        if ($force || $this->shouldRegenerate()) {
            $this->cache->setMultiple([
                $this->key => $this->settings,
                "$this->key:time" => now(),
            ], $this->ttl);
        }
    }

    /**
     * Invalidates the cache of the setting's user.
     *
     * @return void
     */
    public function invalidate(): void
    {
        $this->cache->forget($this->key);
        $this->cache->forget("$this->key:time");

        // Update the time of the last invalidation.
        $this->invalidatedAt = now();
    }

    /**
     * Invalidate the settings cache if it has not been done before.
     *
     * @return void
     */
    public function invalidateIfNotInvalidated(): void
    {
        if (! $this->invalidatedAt) {
            $this->invalidate();
        }
    }

    /**
     * Marks the settings cache to regenerate on exit.
     *
     * @return void
     */
    public function regenerateOnExit(): void
    {
        // Just a simple trick to regenerate only if it's enabled.
        $this->settings->regeneratesOnExit = $this->automaticRegeneration;
    }

    /**
     * representation of object.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return []; // Do not serialize this.
    }

    /**
     * Constructs the object.
     *
     * @param  string  $data
     *
     * @return void
     */
    public function __unserialize($data): void
    {
        // Don't unserialize from anything.
    }

    /**
     * String representation of object.
     *
     * @return string|null
     */
    public function serialize(): ?string
    {
        return null; // Do not serialize this.
    }

    /**
     * Constructs the object.
     *
     * @param string $data
     *
     * @return void
     */
    public function unserialize(string $data): void
    {
        // Don't unserialize from anything.
    }

    /**
     * Creates a new instance.
     *
     * @param Config  $config
     * @param Factory $factory
     * @param Model   $model
     *
     * @return static
     */
    public static function make(Config $config, Factory $factory, Model $model): static
    {
        return new static(
            $factory->store($config->get('laraconfig.cache.store')),
            MorphManySettings::generateKeyForModel(
                $config->get('laraconfig.cache.prefix', 'laraconfig'),
                $model->getMorphClass(),
                $model->getKey()
            ),
            $config->get('laraconfig.cache.ttl', 60 * 60 * 3),
            $config->get('laraconfig.cache.automatic', true),
        );
    }
}
