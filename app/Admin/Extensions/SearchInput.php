<?php


namespace App\Admin\Extensions;


use Encore\Admin\Form\Field;
use Encore\Admin\Form\Field\HasValuePicker;
use Encore\Admin\Form\Field\PlainInput;

class SearchInput  extends Field
{
    use PlainInput;
    use HasValuePicker;

    protected $view = 'admin.search-input';

    /**
     * @var string
     */
    protected $prepend;

    /**
     * @var string
     */
    protected $append;

    public function render()
    {
        $this->defaultAttribute('type', 'text')
            ->defaultAttribute('id', $this->id)
            ->defaultAttribute('name', $this->elementName ?: $this->formatName($this->column))
            ->defaultAttribute('value', old($this->elementName ?: $this->column, $this->value()))
            ->defaultAttribute('class', 'form-control '.$this->getElementClassString())
            ->defaultAttribute('placeholder', $this->getPlaceholder())
            ->mountPicker()
            ->addVariables([
                'prepend' => $this->prepend,
                'append'  => $this->append,
            ]);

        return parent::render();
    }


    /**
     * @param string $attribute
     * @param string $value
     *
     * @return $this
     */
    protected function defaultAttribute($attribute, $value)
    {
        if (!array_key_exists($attribute, $this->attributes)) {
            $this->attribute($attribute, $value);
        }

        return $this;
    }

}
