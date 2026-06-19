<?php

namespace App\Http\Requests\Admin;

use App\Enums\CalendarEventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

/**
 * Validacion para actualizar un evento de calendario existente.
 *
 * Mantiene las mismas reglas que `StoreCalendarEventRequest` para
 * evitar divergencias entre el alta y la edicion. Se reusa
 * `eventData()` para centralizar la conversion de tipos y la
 * normalizacion de fechas all-day.
 */
class UpdateCalendarEventRequest extends FormRequest
{
    /**
     * Pre-chequeo de rol admin.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::in([
                CalendarEventType::Meeting->value,
                CalendarEventType::Milestone->value,
            ])],
            'is_all_day' => ['nullable', 'boolean'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'attendees' => ['nullable', 'array'],
            'attendees.*' => ['integer', 'exists:users,id'],
        ];
    }

    /**
     * Mensajes en castellano, identicos al alta para que el
     * usuario vea el mismo wording al editar.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Introduce un titulo para el evento.',
            'title.min' => 'El titulo es demasiado corto.',
            'title.max' => 'El titulo es demasiado largo.',
            'description.max' => 'La descripcion es demasiado larga.',
            'type.required' => 'Selecciona un tipo de evento.',
            'type.in' => 'El tipo de evento seleccionado no es valido.',
            'starts_at.required' => 'Indica la fecha y hora de inicio.',
            'starts_at.date' => 'La fecha de inicio no es valida.',
            'ends_at.date' => 'La fecha de fin no es valida.',
            'ends_at.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la de inicio.',
            'attendees.array' => 'La lista de asistentes no es valida.',
            'attendees.*.exists' => 'Uno de los asistentes seleccionados no existe.',
        ];
    }

    /**
     * Datos saneados listos para `update()`. Centraliza la
     * conversion del enum a su valor string y la normalizacion
     * de fechas en eventos all-day: si `is_all_day` es true,
     * `starts_at` se lleva a 00:00:00 y `ends_at` a 23:59:59 del
     * mismo dia. La logica es identica al alta para que ambas
     * vias persistan la misma forma.
     *
     * @return array<string, mixed>
     */
    public function eventData(): array
    {
        $data = [
            'title' => trim($this->validated('title')),
            'description' => $this->validated('description'),
            'type' => $this->validated('type'),
            'is_all_day' => $this->boolean('is_all_day'),
            'starts_at' => $this->validated('starts_at'),
            'ends_at' => $this->validated('ends_at'),
        ];

        if ($data['is_all_day']) {
            $start = Carbon::parse($data['starts_at']);
            $data['starts_at'] = $start->copy()->startOfDay();
            $data['ends_at'] = $data['ends_at']
                ? Carbon::parse($data['ends_at'])->endOfDay()
                : $start->copy()->endOfDay();
        }

        return $data;
    }
}
