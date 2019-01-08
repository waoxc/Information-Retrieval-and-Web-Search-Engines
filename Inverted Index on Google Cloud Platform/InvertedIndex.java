import java.io.IOException;
import java.util.StringTokenizer;
import java.util.HashMap;
import org.apache.hadoop.*;
import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.io.IntWritable;
import org.apache.hadoop.io.Text;
import org.apache.hadoop.mapreduce.Job;
import org.apache.hadoop.mapreduce.Mapper;
import org.apache.hadoop.mapreduce.Reducer;
import org.apache.hadoop.mapreduce.lib.input.FileInputFormat;
import org.apache.hadoop.mapreduce.lib.output.FileOutputFormat;


public class InvertedIndex {

  public static class TokenizerMapper
       extends Mapper<Object, Text, Text, Text>{

    private Text word = new Text();

    public void map(Object key, Text value, Context context
                    ) throws IOException, InterruptedException {
      String pattern = "[^a-zA-Z]";
      String[] strings = value.toString().split("\t");
      String id = strings[0];
      for(int i=1; i<strings.length; i++) {
        StringTokenizer st = new StringTokenizer(strings[i].toLowerCase().replaceAll(pattern, " "));
        while (st.hasMoreTokens()) {
          word.set(st.nextToken());
          context.write(word, new Text(id));
        }
      }
    }
  }
  public static class IntSumReducer
       extends Reducer<Text, Text, Text, Text> {
    public void reduce(Text key, Iterable<Text> values,
                       Context context
                       ) throws IOException, InterruptedException {
      HashMap<String, Integer> idToCount = new HashMap<>();
      for (Text val : values) {
        String s = val.toString();
        if(idToCount.containsKey(s))
          idToCount.put(s, idToCount.get(s)+1);
        else
          idToCount.put(s, 1);
      }
      String result = "";
      for(String s: idToCount.keySet()){
        result += s + ":" + idToCount.get(s) + "\t";
      }
      context.write(key, new Text(result));
    }
  }

  public static void main(String[] args) throws Exception {
    Configuration conf = new Configuration();
    Job job = Job.getInstance(conf, "word count");
    job.setJarByClass(InvertedIndex.class);
    job.setMapperClass(TokenizerMapper.class);
    job.setReducerClass(IntSumReducer.class);
    job.setOutputKeyClass(Text.class);
    job.setOutputValueClass(Text.class);
    FileInputFormat.addInputPath(job, new Path(args[0]));
    FileOutputFormat.setOutputPath(job, new Path(args[1]));
    System.exit(job.waitForCompletion(true) ? 0 : 1);
  }
}