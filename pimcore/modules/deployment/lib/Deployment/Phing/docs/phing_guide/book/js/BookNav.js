/**
 * This function checks the href of the content
 * frame against a known list of chapters.
 * 
 * If the href is found, the index to that href in
 * the Chapters array is returned, if not null.
 * 
 * @author  alex, alex@binarycloud.com
 */
function GetCurrentChapter() {

    var ContentLocation = top.ContentAndToC.Content.location.href;
    var CurrentChapter = null;

    for (i=0; i < Chapters.length; i++) {
        if (ContentLocation.indexOf(Chapters[i][0]) != -1) {
            CurrentChapter = i;
        }
    }

    return CurrentChapter;
}

/**
 * This function gets the current chapter and
 * sets the Content href to the previous
 * chapter. If the current chapter is "0",
 * the href is set to the Standard ToC,
 * (index.html).
 * 
 * @author  alex, alex@binarycloud.com
 */
function GoBack() {

    var CurrentChapter = GetCurrentChapter();

    if (CurrentChapter == 0) {
        top.ContentAndToC.Content.location = "../toc/ToC.html";
    } else {
        top.ContentAndToC.Content.location = "../"+Chapters[CurrentChapter-1][0];
    }

}

/**
 * This function gets the current chapter
 * and sets the content href to the next
 * chapter. If the href is "index.html"
 * we know we're on the standard ToC, so
 * the href should be set to the first
 * chapter (0). If we're on the last
 * chapter, we return to the standard
 * ToC.
 * 
 * @author  alex, alex@binarycloud.com
 */
function GoNext() {

    var CurrentChapter = GetCurrentChapter();

    if (CurrentChapter == Chapters.length-1) {
        top.ContentAndToC.Content.location = "../toc/ToC.html";
    } else if (CurrentChapter == null) {
        top.ContentAndToC.Content.location = "../"+Chapters[0][0];
    } else {
        top.ContentAndToC.Content.location = "../"+Chapters[CurrentChapter+1][0];
    }
}
