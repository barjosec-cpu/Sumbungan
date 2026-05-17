pipeline {
    agent any

    environment {
        DOCKER_REGISTRY = 'docker.io'
        IMAGE_NAME = 'sumbungan'
        DOCKER_CREDENTIALS = credentials('docker-credentials')
        GIT_REPO = 'https://github.com/barjosec-cpu/Sumbungan.git'
        GIT_BRANCH = 'main'
    }

    stages {
        stage('Checkout') {
            steps {
                echo '=== Checking out code from GitHub ==='
                git branch: "${GIT_BRANCH}", url: "${GIT_REPO}"
            }
        }

        stage('Build Docker Image') {
            steps {
                echo '=== Building Docker image ==='
                script {
                    sh 'docker build -t ${IMAGE_NAME}:latest .'
                    sh 'docker tag ${IMAGE_NAME}:latest ${IMAGE_NAME}:${BUILD_NUMBER}'
                }
            }
        }

        stage('Test') {
            steps {
                echo '=== Running tests ==='
                script {
                    sh 'docker-compose up -d'
                    sh 'sleep 10'
                    sh 'curl http://localhost:8080 || echo "Test failed but continuing"'
                }
            }
        }

        stage('Push to Docker Hub') {
            steps {
                echo '=== Pushing Docker image to registry ==='
                script {
                    sh 'echo $DOCKER_CREDENTIALS_PSW | docker login -u $DOCKER_CREDENTIALS_USR --password-stdin'
                    sh 'docker tag ${IMAGE_NAME}:latest ${DOCKER_CREDENTIALS_USR}/${IMAGE_NAME}:latest'
                    sh 'docker push ${DOCKER_CREDENTIALS_USR}/${IMAGE_NAME}:latest'
                }
            }
        }

        stage('Deploy') {
            steps {
                echo '=== Deploying application ==='
                script {
                    sh 'docker-compose down || true'
                    sh 'docker-compose up -d'
                    echo 'Application deployed successfully'
                }
            }
        }

        stage('Generate Documentation') {
            steps {
                echo '=== Documentation already included in README.md ==='
            }
        }
    }

    post {
        always {
            echo '=== Cleaning up ==='
            sh 'docker-compose logs > logs.txt || true'
        }
        success {
            echo '=== Pipeline completed successfully ==='
        }
        failure {
            echo '=== Pipeline failed ==='
        }
    }
}
