<?php declare(strict_types=1);

namespace Cockpit\Collections;

use Cockpit\Framework\IDs;

final class Collection
{
    /** @var string */
    private $id;
    /** @var string */
    private $name;
    /** @var string */
    private $label;
    /** @var string */
    private $description;
    /** @var string */
    private $color;
    /** @var Field[] */
    private $fields;
    /** @var array */
    private $acl;
    /** @var bool */
    private $sortable;
    /** @var bool */
    private $inMenu;
    /** @var array */
    private $rules;

    public static function create()
    {
        return new self(IDs::new(), '', '', '', '', [], [], false, false);
    }

    public function __construct(string $id, string $name, string $label, string $description, string $color, $fields, array $acl, bool $sortable, bool $inMenu)
    {
        $this->id = $id;
        $this->name = $name;
        $this->label = $label;
        $this->color = $color;
        $this->fields = $fields;
        $this->acl = $acl;
        $this->sortable = $sortable;
        $this->inMenu = $inMenu;
        $this->rules = [
            'create' => ['enabled' => false],
            'read' => ['enabled' => false],
            'update' => ['enabled' => false],
            'delete' => ['enabled' => false]
        ];
        $this->description = $description;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function color(): string
    {
        return $this->color;
    }

    public function sortable(): bool
    {
        return $this->sortable;
    }

    /**
     * @return Field[]
     */
    public function fields(): array
    {
        return $this->fields;
    }

    public function hasLocalizedFields(): bool
    {
        $localized = array_filter($this->fields, function (Field $field) {
            return $field->localize();
        });

        return count($localized) > 0;
    }

    public function hasAccess($role): bool
    {
        return true;
    }

    public function toArray(): array
    {
        $data = [
            '_id' => $this->id,
            'name' => $this->name,
            'label' => $this->label,
            'description' => $this->description,
            'color' => $this->color,
            'fields' => array_map(function (Field $field) {
                return $field->toArray();
            }, $this->fields),
            'sortable' => (bool)$this->sortable,
            'in_menu' => (bool)$this->inMenu,
            '_created' => (new \DateTimeImmutable())->getTimestamp(),
            '_updated' => (new \DateTimeImmutable())->getTimestamp(),
            'acl' => [],
            'rules' => $this->rules,

            // Things templates need
            'icon' => '',
            'meta' => [
                'allowed' => [
                    'delete' => true,
                    'create' => true,
                    'edit' => true,
                    'entries_create' => true,
                    'entries_delete' => true
                ]
            ]
        ];

        return $data;
    }
}
