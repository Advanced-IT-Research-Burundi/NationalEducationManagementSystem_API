@php
    $layout = \App\Support\BulletinCourseLayout::partitionForPdf($bulletin['cours']);
    $fmt = fn ($value) => \App\Support\BulletinCourseLayout::formatNote($value);
@endphp

@foreach ($layout['groups'] as $groupIndex => $group)
    @php $catTotals = \App\Support\BulletinCourseLayout::computeGroupTotals($group['items'], true); @endphp
    @foreach ($group['items'] as $courseIndex => $cours)
        @php
            $t1 = $cours['trimestres']['1er Trimestre'] ?? null;
            $t2 = $cours['trimestres']['2e Trimestre'] ?? null;
            $t3 = $cours['trimestres']['3e Trimestre'] ?? null;
            $annuel = $cours['annuel'] ?? null;
            $hasComp = ($cours['has_competence_track'] ?? false) && ($cours['max_competence'] ?? 0) > 0;
        @endphp
        <tr>
            @if ($courseIndex === 0)
                <td rowspan="{{ count($group['items']) + 1 }}">{{ $groupIndex + 1 }}</td>
                <td rowspan="{{ count($group['items']) + 1 }}">{{ $group['name'] }}</td>
            @endif

            <td class="td-name">{{ $cours['nom'] }}</td>
            <td>1</td>
            <td>{{ $fmt($cours['max_tj'] ?? null) }}</td>
            <td>{{ $hasComp ? $fmt($cours['max_competence']) : '—' }}</td>
            <td>{{ $fmt($cours['max_examen'] ?? null) }}</td>
            <td><strong>{{ $fmt($cours['max_total'] ?? null) }}</strong></td>

            <td>{{ $fmt($t1['note_tj'] ?? null) }}</td>
            <td>{{ $hasComp ? $fmt($t1['note_competence'] ?? null) : '—' }}</td>
            <td>{{ $fmt($t1['note_examen'] ?? null) }}</td>
            <td><strong>{{ $fmt($t1['note_total'] ?? null) }}</strong></td>

            <td>{{ $fmt($t2['note_tj'] ?? null) }}</td>
            <td>{{ $hasComp ? $fmt($t2['note_competence'] ?? null) : '—' }}</td>
            <td>{{ $fmt($t2['note_examen'] ?? null) }}</td>
            <td><strong>{{ $fmt($t2['note_total'] ?? null) }}</strong></td>

            <td>{{ $fmt($t3['note_tj'] ?? null) }}</td>
            <td>{{ $hasComp ? $fmt($t3['note_competence'] ?? null) : '—' }}</td>
            <td>{{ $fmt($t3['note_examen'] ?? null) }}</td>
            <td><strong>{{ $fmt($t3['note_total'] ?? null) }}</strong></td>

            <td>{{ $fmt($annuel['max_total'] ?? null) }}</td>
            <td>{{ $fmt($annuel['note_total'] ?? null) }}</td>
            <td></td>
            <td></td>
        </tr>
    @endforeach
    <tr style="font-weight: bold; background-color: #f5f5f5;">
        <td style="text-align: left; padding-left: 5px;">Total</td>
        <td>-</td>
        <td>{{ ($catTotals['max_tj'] ?? 0) > 0 ? $fmt($catTotals['max_tj']) : '—' }}</td>
        <td>{{ ($catTotals['max_com'] ?? 0) > 0 ? $fmt($catTotals['max_com']) : '—' }}</td>
        <td>{{ $fmt($catTotals['max_res']) }}</td>
        <td>{{ $fmt($catTotals['max_tot']) }}</td>

        <td>{{ $catTotals['trimestres']['1er Trimestre']['has_tj'] && $catTotals['trimestres']['1er Trimestre']['tj_complete'] ? $fmt($catTotals['trimestres']['1er Trimestre']['tj']) : '' }}</td>
        <td>{{ ($catTotals['max_com'] ?? 0) > 0 ? ($catTotals['trimestres']['1er Trimestre']['has_com'] && $catTotals['trimestres']['1er Trimestre']['com_complete'] ? $fmt($catTotals['trimestres']['1er Trimestre']['com']) : '') : '—' }}</td>
        <td>{{ $catTotals['trimestres']['1er Trimestre']['has_res'] && $catTotals['trimestres']['1er Trimestre']['res_complete'] ? $fmt($catTotals['trimestres']['1er Trimestre']['res']) : '' }}</td>
        <td>{{ $catTotals['trimestres']['1er Trimestre']['has_tot'] && $catTotals['trimestres']['1er Trimestre']['tot_complete'] ? $fmt($catTotals['trimestres']['1er Trimestre']['tot']) : '' }}</td>

        <td>{{ $catTotals['trimestres']['2e Trimestre']['has_tj'] && $catTotals['trimestres']['2e Trimestre']['tj_complete'] ? $fmt($catTotals['trimestres']['2e Trimestre']['tj']) : '' }}</td>
        <td>{{ ($catTotals['max_com'] ?? 0) > 0 ? ($catTotals['trimestres']['2e Trimestre']['has_com'] && $catTotals['trimestres']['2e Trimestre']['com_complete'] ? $fmt($catTotals['trimestres']['2e Trimestre']['com']) : '') : '—' }}</td>
        <td>{{ $catTotals['trimestres']['2e Trimestre']['has_res'] && $catTotals['trimestres']['2e Trimestre']['res_complete'] ? $fmt($catTotals['trimestres']['2e Trimestre']['res']) : '' }}</td>
        <td>{{ $catTotals['trimestres']['2e Trimestre']['has_tot'] && $catTotals['trimestres']['2e Trimestre']['tot_complete'] ? $fmt($catTotals['trimestres']['2e Trimestre']['tot']) : '' }}</td>

        <td>{{ $catTotals['trimestres']['3e Trimestre']['has_tj'] && $catTotals['trimestres']['3e Trimestre']['tj_complete'] ? $fmt($catTotals['trimestres']['3e Trimestre']['tj']) : '' }}</td>
        <td>{{ ($catTotals['max_com'] ?? 0) > 0 ? ($catTotals['trimestres']['3e Trimestre']['has_com'] && $catTotals['trimestres']['3e Trimestre']['com_complete'] ? $fmt($catTotals['trimestres']['3e Trimestre']['com']) : '') : '—' }}</td>
        <td>{{ $catTotals['trimestres']['3e Trimestre']['has_res'] && $catTotals['trimestres']['3e Trimestre']['res_complete'] ? $fmt($catTotals['trimestres']['3e Trimestre']['res']) : '' }}</td>
        <td>{{ $catTotals['trimestres']['3e Trimestre']['has_tot'] && $catTotals['trimestres']['3e Trimestre']['tot_complete'] ? $fmt($catTotals['trimestres']['3e Trimestre']['tot']) : '' }}</td>

        <td>{{ $fmt($catTotals['annuel']['max_tot']) }}</td>
        <td>{{ $catTotals['annuel']['has_tot'] && $catTotals['annuel']['is_complete'] ? $fmt($catTotals['annuel']['tot']) : '' }}</td>
        <td></td>
        <td></td>
    </tr>
@endforeach

@foreach ($layout['standalone'] as $cours)
    @php
        $t1 = $cours['trimestres']['1er Trimestre'] ?? null;
        $t2 = $cours['trimestres']['2e Trimestre'] ?? null;
        $t3 = $cours['trimestres']['3e Trimestre'] ?? null;
        $annuel = $cours['annuel'] ?? null;
        $hasComp = ($cours['has_competence_track'] ?? false) && ($cours['max_competence'] ?? 0) > 0;
    @endphp
    <tr>
        <td colspan="3" class="td-name">{{ $cours['nom'] }}</td>
        <td>1</td>
        <td>{{ $fmt($cours['max_tj'] ?? null) }}</td>
        <td>{{ $hasComp ? $fmt($cours['max_competence']) : '—' }}</td>
        <td>{{ $fmt($cours['max_examen'] ?? null) }}</td>
        <td><strong>{{ $fmt($cours['max_total'] ?? null) }}</strong></td>

        <td>{{ $fmt($t1['note_tj'] ?? null) }}</td>
        <td>{{ $hasComp ? $fmt($t1['note_competence'] ?? null) : '—' }}</td>
        <td>{{ $fmt($t1['note_examen'] ?? null) }}</td>
        <td><strong>{{ $fmt($t1['note_total'] ?? null) }}</strong></td>

        <td>{{ $fmt($t2['note_tj'] ?? null) }}</td>
        <td>{{ $hasComp ? $fmt($t2['note_competence'] ?? null) : '—' }}</td>
        <td>{{ $fmt($t2['note_examen'] ?? null) }}</td>
        <td><strong>{{ $fmt($t2['note_total'] ?? null) }}</strong></td>

        <td>{{ $fmt($t3['note_tj'] ?? null) }}</td>
        <td>{{ $hasComp ? $fmt($t3['note_competence'] ?? null) : '—' }}</td>
        <td>{{ $fmt($t3['note_examen'] ?? null) }}</td>
        <td><strong>{{ $fmt($t3['note_total'] ?? null) }}</strong></td>

        <td>{{ $fmt($annuel['max_total'] ?? null) }}</td>
        <td>{{ $fmt($annuel['note_total'] ?? null) }}</td>
        <td></td>
        <td></td>
    </tr>
@endforeach
