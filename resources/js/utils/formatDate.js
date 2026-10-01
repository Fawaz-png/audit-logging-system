const parseCalendarDate = (value) => {
    if (!value) {
        return null
    }

    if (typeof value === 'string') {
        const calendarDate = value.match(/^(\d{4})-(\d{2})-(\d{2})/)

        if (calendarDate) {
            return new Date(Number(calendarDate[1]), Number(calendarDate[2]) - 1, Number(calendarDate[3]))
        }
    }

    const date = new Date(value)

    return Number.isNaN(date.getTime()) ? null : date
}

const parseTimestamp = (value) => {
    if (!value) {
        return null
    }

    const date = new Date(value)

    return Number.isNaN(date.getTime()) ? null : date
}

export const formatDate = (value) => {
    const date = parseCalendarDate(value)

    return date
        ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(date)
        : '—'
}

export const formatDateTime = (value) => {
    const date = parseTimestamp(value)

    return date
        ? new Intl.DateTimeFormat(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(date)
        : '—'
}