import java.io.*;

import org.apache.tika.exception.TikaException;
import org.apache.tika.metadata.Metadata;
import org.apache.tika.parser.ParseContext;
import org.apache.tika.parser.html.HtmlParser;
import org.apache.tika.sax.BodyContentHandler;

import org.xml.sax.SAXException;

class TikaParser {

    public static void main(final String[] args) throws IOException, SAXException, TikaException {
        File dir = new File("C:\\Users\\anind\\Downloads\\FOX");
        BufferedWriter writer = new BufferedWriter(new FileWriter("big_fox.txt"));
        int count = 0;
        File[] files = dir.listFiles();
        assert files != null;
        for (File file: files){
            BodyContentHandler handler = new BodyContentHandler(-1);
            //Html parser
            HtmlParser htmlparser = new HtmlParser();
            htmlparser.parse(new FileInputStream(file), handler, new Metadata(), new ParseContext());

            //handler string.
            String text = handler.toString();
            //replace whitespaces.
            text = text.replaceAll("\n", " ").replaceAll("\t", " ").replaceAll(" +", " ");
            writer.write(text + "\n");
            count++;
            if(count % 100 == 0)
                System.out.println(count +" of " + files.length);
        }
        writer.close();
    }
}