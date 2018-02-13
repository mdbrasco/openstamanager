<?php

namespace Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'zz_modules';

    protected $appends = [
        'permission',
        'option',
    ];

    protected $hidden = [
        'options',
        'options2',
    ];

    /**
     * Restituisce i permessi relativi all'account in utilizzo.
     *
     * @return string
     */
    public function getPermissionAttribute()
    {
        if (\Auth::admin()) {
            return 'rw';
        }

        $user = \App::getUser();

        $modules = $user->modules()->get();

        // Rimozione dei moduli non relativi
        $modules = $modules->reject(function ($module) {
            return $module->id != $this->id;
        });

        return $modules->first()->pivot['permessi'];
    }

    /**
     * Restituisce i permessi relativi all'account in utilizzo.
     *
     * @return string
     */
    public function getViewsAttribute()
    {
        $database = \Database::getConnection();

        $user = \App::getUser();

        $views = $database->fetchArray('SELECT * FROM `zz_views` WHERE `id_module`='.prepare($this->id).' AND
        `id` IN (
            SELECT `id_vista` FROM `zz_group_view` WHERE `id_gruppo`=(
                SELECT `idgruppo` FROM `zz_users` WHERE `id`='.prepare($user->id).'
            ))
        ORDER BY `order` ASC');

        return $views;
    }

    public function getOptionAttribute()
    {
        return !empty($this->options) ? $this->options : $this->options2;
    }

    public function getOptionsAttribute($value)
    {
        return \App::replacePlaceholder($value);
    }

    public function getOptions2Attribute($value)
    {
        return \App::replacePlaceholder($value);
    }

    /* Relazioni Eloquent */

    public function plugins()
    {
        return $this->hasMany(Plugin::class, 'idmodule_to')->active();
    }

    public function prints()
    {
        return $this->hasMany(PrintTemplate::class, 'id_module');
    }

    public function views()
    {
        return $this->hasMany(View::class, 'id_module');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'zz_permissions', 'idmodule', 'idgruppo');
    }

    public function clauses()
    {
        return $this->hasMany(Clause::class, 'idmodule');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent')
            ->orderBy('order');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent');
    }

    // all ascendants
    public function allParents()
    {
        return $this->parent()->with('allParents');
    }

    // loads all descendants
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    /* Metodi statici */

    /**
     * Scope a query to only include active users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('enabled', true);
    }

    public static function get($element)
    {
        return parent::active()
            ->where('id', $element)
            ->orWhere('name', $element)
            ->first();
    }

    public static function getHierarchy()
    {
        return self::with('allChildren')
            ->whereNull('parent')
            ->orderBy('order')
            ->get();
    }

    /**
     * Undocumented function.
     *
     * @param string|int $modulo
     * @param int        $id_record
     * @param string     $testo
     * @param string     $alternativo
     * @param string     $extra
     *
     * @return string
     */
    public static function link($modulo, $id_record = null, $testo = null, $alternativo = true, $extra = null, $blank = true)
    {
        $testo = isset($testo) ? nl2br($testo) : tr('Visualizza scheda');
        $alternativo = is_bool($alternativo) && $alternativo ? $testo : $alternativo;

        // Aggiunta automatica dell'icona di riferimento
        if (!str_contains($testo, '<i ')) {
            $testo = $testo.' <i class="fa fa-external-link"></i>';
        }

        $module = self::get($modulo);

        $extra .= !empty($blank) ? ' target="_blank"' : '';

        if (!empty($module) && in_array($module['permessi'], ['r', 'rw'])) {
            $link = !empty($id_record) ? 'editor.php?id_module='.$module['id'].'&id_record='.$id_record : 'controller.php?id_module='.$module['id'];

            return '<a href="'.ROOTDIR.'/'.$link.'" '.$extra.'>'.$testo.'</a>';
        } else {
            return $alternativo;
        }
    }
}