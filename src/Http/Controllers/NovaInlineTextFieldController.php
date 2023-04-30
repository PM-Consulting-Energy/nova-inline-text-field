<?php

namespace Outl1ne\NovaInlineTextField\Http\Controllers;

use Exception;
use Illuminate\Routing\Controller;
use Laravel\Nova\Panel;
use Outl1ne\NovaInlineTextField\InlineText;
use Laravel\Nova\Http\Requests\NovaRequest;

class NovaInlineTextFieldController extends Controller
{
    public function update(NovaRequest $request)
    {
        $modelId = $request->_inlineResourceId;
        $attribute = $request->_inlineAttribute;

        $resourceClass = $request->resource();
        $resourceValidationRules = $resourceClass::rulesForUpdate($request);
        $fieldValidationRules = $resourceValidationRules[$attribute] ?? null;

        if (!empty($fieldValidationRules)) {
            $request->validate([$attribute => $fieldValidationRules]);
        }

        // Find field in question
        try {
            $model = $request->model()->find($modelId);

            $resource = $request->newResourceWith($model);

            $allFields = collect($resource->fields($request))->map(function($field) {
                if (get_class($field) === Panel::class) {
                    return $field->data;
                }

                return [$field];
            })->flatten(1);

            $field = collect($allFields)->first(function ($field) use ($attribute) {
                return get_class($field) === InlineText::class && $field->attribute === $attribute;
            });

            $field->fillInto($request, $model, $attribute);
            $model->save();

            $field->resolve($model, $attribute);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['value' => $field->value]);
    }
}
