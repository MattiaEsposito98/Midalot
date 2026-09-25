import jsPDF from 'jspdf'

/**
 * Scarica un PDF con domande e risposte di un quiz Midalario, senza
 * indicare quale risposta e' corretta (foglio da consultare durante
 * l'evento senza spoilerare nulla). Per audio/immagini/video, che il
 * PDF non puo' mostrare, stampa solo una nota testuale.
 */
export async function generaMidalarioPdf(quizId) {
    const response = await fetch(`/admin/midalario/${quizId}/pdf-data`, {
        headers: { Accept: 'application/json' },
    })

    if (!response.ok) {
        alert('Impossibile generare il PDF: errore nel recupero delle domande.')
        return
    }

    const data = await response.json()

    const doc = new jsPDF({ unit: 'pt', format: 'a4' })
    const marginX = 48
    const pageWidth = doc.internal.pageSize.getWidth()
    const pageHeight = doc.internal.pageSize.getHeight()
    const contentWidth = pageWidth - marginX * 2
    let y = 64

    function ensureSpace(neededHeight) {
        if (y + neededHeight > pageHeight - 48) {
            doc.addPage()
            y = 64
        }
    }

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(18)
    doc.setTextColor('#182033')
    doc.text('IL MIDALARIO', pageWidth / 2, y, { align: 'center' })
    y += 22
    doc.setFont('helvetica', 'normal')
    doc.setFontSize(12)
    doc.setTextColor('#647084')
    doc.text(data.title, pageWidth / 2, y, { align: 'center' })
    y += 14
    doc.setFontSize(9)
    doc.text('Domande e risposte, senza indicazione della risposta corretta', pageWidth / 2, y, { align: 'center' })
    y += 24
    doc.setDrawColor('#ffc107')
    doc.setLineWidth(2)
    doc.line(marginX, y, pageWidth - marginX, y)
    y += 28

    data.questions.forEach((question, index) => {
        ensureSpace(60)

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(12)
        doc.setTextColor('#182033')
        const questionLines = doc.splitTextToSize(`${index + 1}. ${question.question_text}`, contentWidth)
        doc.text(questionLines, marginX, y)
        y += questionLines.length * 15 + 4

        const note = mediaNote(question)
        if (note) {
            doc.setFont('helvetica', 'italic')
            doc.setFontSize(10)
            doc.setTextColor('#647084')
            doc.text(note, marginX, y)
            y += 16
        }

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(11)
        doc.setTextColor('#222222')

        question.answers.forEach((answerText, answerIndex) => {
            ensureSpace(18)
            const letter = String.fromCharCode(65 + answerIndex)
            const lines = doc.splitTextToSize(`${letter}) ${answerText}`, contentWidth - 12)
            doc.text(lines, marginX + 12, y)
            y += lines.length * 14
        })

        y += 16
    })

    const pageCount = doc.internal.getNumberOfPages()
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i)
        doc.setFont('helvetica', 'normal')
        doc.setFontSize(8)
        doc.setTextColor('#647084')
        doc.text(`Pagina ${i} di ${pageCount}`, pageWidth / 2, pageHeight - 24, { align: 'center' })
    }

    doc.save(`midalario-${slugify(data.title)}.pdf`)
}

function mediaNote(question) {
    const notes = []
    if (question.has_audio) notes.push('🎵 Questa domanda contiene una canzone/audio')
    if (question.has_image) notes.push('🖼️ Questa domanda contiene un\'immagine')
    if (question.has_video) notes.push('🎬 Questa domanda contiene un video')
    return notes.join(' — ')
}

function slugify(text) {
    return text
        .toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '') || 'quiz'
}
