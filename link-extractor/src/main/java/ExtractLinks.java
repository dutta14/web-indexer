import com.opencsv.CSVReader;
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;

import java.io.*;
import java.util.*;

/**
 * @author Anindya Dutta
 * @version 4/7/2018
 */

public class ExtractLinks {

    private static final String PATH = "C:\\Users\\anind\\Downloads\\NBC_News-20180407T052036Z-001\\NBC_News\\HTML files\\HTML files";
    private static final String CSV_PATH = "C:\\Users\\anind\\Downloads\\NBC_News-20180407T052036Z-001\\NBC_News\\UrlToHtml_NBCNews.csv";

    private HashMap<String, String> fileUrlMap, urlFileMap;

    private void createMap() {
        fileUrlMap = new HashMap<>();
        urlFileMap = new HashMap<>();
        CSVReader reader = null;
        try {
            reader = new CSVReader(new FileReader(CSV_PATH));
            String[] line;
            while ((line = reader.readNext()) != null) {
                fileUrlMap.put(line[0], line[1]);
                urlFileMap.put(line[1], line[0]);
            }
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    private Set<String> createEdges() throws IOException {
        File dir = new File(PATH);
        Set<String> edges = new HashSet<>();

        for(File file: dir.listFiles()) {
            Document doc = Jsoup.parse(file, "UTF-8", fileUrlMap.get(file.getName()));
            Elements links = doc.select("a[href]");
            Elements pngs = doc.select("[src]");

            for(Element link: links) {
                String url = link.attr("abs:href").trim();
                if(urlFileMap.containsKey(url)) {
                    edges.add(file.getName() + " " + urlFileMap.get(url));
                }
            }
        }
        return edges;
    }

    private void writeFile(String path, Set<String> edges) throws IOException {
        PrintWriter writer = new PrintWriter(new BufferedWriter(new FileWriter(path)));
        for(String edge: edges) {
            writer.println(edge);
        }
        writer.flush();
        writer.close();
        System.out.println(edges.size());
    }

    public static void main(String[] args) throws IOException {
        ExtractLinks obj = new ExtractLinks();
        obj.createMap();
        Set<String> edges = obj.createEdges();
        obj.writeFile("edgeList.txt", edges);
    }
}
