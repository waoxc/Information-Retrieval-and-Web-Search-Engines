import org.jsoup.*;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;

import java.util.*;
import java.io.*;

public class edgelist {
    public static void main(String[] args) throws IOException{
        PrintWriter writer = new PrintWriter("edgeList.txt");
        Map<String, String> fileUrlMap = new HashMap<>();
        Map<String, String> urlFileMap = new HashMap<>();

        String fileToRead = "/Users/xuechengzhe/URLtoHTML_nypost.csv";
        BufferedReader fileReader = null;
        String line = "";
        fileReader = new BufferedReader(new FileReader(fileToRead));
        while ((line = fileReader.readLine()) != null) {
            String[] tokens = line.split(",");
            fileUrlMap.put(tokens[0],tokens[1]);
            urlFileMap.put(tokens[1],tokens[0]);
        }

        File dir = new File("/Users/xuechengzhe/nypost");
        Set<String> edges = new HashSet<>();
        for(File file: dir.listFiles()){
            Document doc = Jsoup.parse(file, "UTF-8", fileUrlMap.get(file.getName()));
            Elements links = doc.select("a[href]");
            Elements pngs = doc.select("[src]");

            for(Element link: links){
                String url = link.attr("abs:href").trim();
                if(urlFileMap.containsKey(url)){
                    edges.add(file.getName()+" "+urlFileMap.get(url));
                }
            }

        }

        for(String s: edges){
            writer.println(s);
        }

        writer.flush();
        writer.close();
    }
}
