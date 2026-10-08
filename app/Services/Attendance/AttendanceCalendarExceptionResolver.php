<?php

namespace App\Services\Attendance;

use App\Models\AttendanceCalendarException;
use App\Models\Enrollment;
use Carbon\CarbonInterface;

class AttendanceCalendarExceptionResolver
{
    public function resolve(
        Enrollment $enrollment,
        CarbonInterface $date
    ): ?AttendanceCalendarException {
        $enrollment->loadMissing([
            'gradeSection.grade',
        ]);

        $levelId =
            $enrollment
            ->gradeSection
            ->grade
            ->educational_level_id;

        $gradeSectionId =
            $enrollment
            ->grade_section_id;

        return AttendanceCalendarException::query()
            ->with([
                'overrideSchedule.events',
            ])
            ->where(
                'academic_year_id',
                $enrollment->academic_year_id
            )
            ->whereDate(
                'date',
                $date->toDateString()
            )
            ->where(
                'is_active',
                true
            )
            ->where(
                function ($query) use (
                    $levelId,
                    $gradeSectionId
                ) {
                    /*
                     * Aula
                     */
                    $query->where(
                        function ($query) use (
                            $levelId,
                            $gradeSectionId
                        ) {
                            $query
                                ->where(
                                    'educational_level_id',
                                    $levelId
                                )
                                ->where(
                                    'grade_section_id',
                                    $gradeSectionId
                                );
                        }
                    )

                        /*
                     * Nivel
                     */
                        ->orWhere(
                            function ($query) use (
                                $levelId
                            ) {
                                $query
                                    ->where(
                                        'educational_level_id',
                                        $levelId
                                    )
                                    ->whereNull(
                                        'grade_section_id'
                                    );
                            }
                        )

                        /*
                     * Institución
                     */
                        ->orWhere(
                            function ($query) {
                                $query
                                    ->whereNull(
                                        'educational_level_id'
                                    )
                                    ->whereNull(
                                        'grade_section_id'
                                    );
                            }
                        );
                }
            )
            /*
             * Aula > Nivel > Institución
             */
            ->orderByRaw(
                '
                CASE
                    WHEN grade_section_id IS NOT NULL THEN 3
                    WHEN educational_level_id IS NOT NULL THEN 2
                    ELSE 1
                END DESC
                '
            )
            ->first();
    }
}
