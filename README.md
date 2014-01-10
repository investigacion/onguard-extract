# onguard-extract #

A small PHP script for extracting tabular data from Lenel OnGuard 2012 PDF reports.

## Usage ##

Use [Xpdf](http://www.foolabs.com/xpdf/home.html) to first extract text from the PDF. Remember to add the switch to preserve layout.

```bash
pdftotext -enc UTF-8 -layout input.pdf output.txt
```

Now run the script against the extracted text file. Rows are TSV-formatted and sent to standard output.

```bash
php extract.php output.txt > output.tsv
```

The script may be used on extremely large files without any problems as input is streamed line-by-line.

## Credits ##

[Matthew Caruana Galizia](https://twitter.com/mcaruanagalizia) at La Nación, Costa Rica.

## License ##

MIT
