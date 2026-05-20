import jenkins.model.*
import hudson.model.*

def jenkins = Jenkins.getInstanceOrNull()
if (jenkins == null) {
    println "[seed-jobs] Jenkins not ready - skip"
    return
}

def seedDir = new File("/usr/share/jenkins/ref/jobs-seed")
if (!seedDir.exists()) {
    println "[seed-jobs] No jobs-seed directory"
    return
}

def seeded = 0
seedDir.eachDir { File jobDir ->
    def config = new File(jobDir, "config.xml")
    if (!config.exists()) return

    def name = jobDir.name
    def target = new File(jenkins.getRootDir(), "jobs/${name}")
    def targetConfig = new File(target, "config.xml")

    target.mkdirs()
    if (!targetConfig.exists() || config.text != targetConfig.text) {
        targetConfig.text = config.text
        seeded++
        println "[seed-jobs] Installed/updated job: ${name}"
    }
}

jenkins.reload()
println "[seed-jobs] Done (${seeded} job config(s) changed). Jobs: job1, job2, job3"
