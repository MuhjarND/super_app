Option Explicit
Dim wordApp, wordDoc, docPath, pdfPath
docPath = "C:\xampp\htdocs\super3\public\storage\surat-keluar\generated\suket-perbaikan-presensi-987.docx"
pdfPath = "C:\xampp\htdocs\super3\tmp\suket-perbaikan-presensi-template\approved-test-render-2.pdf"
Set wordApp = CreateObject("Word.Application")
wordApp.Visible = False
wordApp.DisplayAlerts = 0
Set wordDoc = wordApp.Documents.Open(docPath, False, True, False, "", "", False, "", "", 0, 0, False, False, False, True)
wordDoc.SaveAs2 pdfPath, 17
wordDoc.Close False
wordApp.Quit
Set wordDoc = Nothing
Set wordApp = Nothing
