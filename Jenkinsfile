pipeline {
    agent any

    environment {
        IMAGE_NAME       = 'sumbungan'
        IMAGE_TAG        = "${env.BUILD_NUMBER}"
        TEST_CONTAINER   = "sumbungan_ci_${env.BUILD_NUMBER}"
        TEST_PORT        = '19080'
    }

    options {
        timestamps()
        timeout(time: 20, unit: 'MINUTES')
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '10'))
    }

    stages {
        stage('Checkout') {
            steps {
                echo "=== Checking out ${env.BRANCH_NAME ?: 'main'} ==="
                checkout scm
                sh 'ls -la'
            }
        }

        stage('PHP Syntax Check') {
            steps {
                echo '=== Linting PHP files ==='
                sh '''
                    docker run --rm -v "$WORKSPACE":/app -w /app php:8.1-cli sh -c '
                        set -e
                        find . -path ./vendor -prune -o -type f -name "*.php" -print | while read f; do
                            php -l "$f" >/dev/null
                        done
                        echo "PHP syntax OK"
                    '
                '''
            }
        }

        stage('Build Docker Image') {
            steps {
                echo '=== Building Docker image ==='
                sh 'docker build -t ${IMAGE_NAME}:${IMAGE_TAG} -t ${IMAGE_NAME}:latest .'
                sh 'docker images ${IMAGE_NAME} --format "table {{.Repository}}\\t{{.Tag}}\\t{{.Size}}\\t{{.CreatedAt}}"'
            }
        }

        stage('Smoke Test') {
            steps {
                echo '=== Smoke testing the built image ==='
                sh '''
                    set -e
                    docker rm -f ${TEST_CONTAINER} >/dev/null 2>&1 || true
                    docker run -d --name ${TEST_CONTAINER} -p ${TEST_PORT}:80 ${IMAGE_NAME}:${IMAGE_TAG}
                    for i in $(seq 1 20); do
                        if docker exec ${TEST_CONTAINER} sh -c "curl -fsS http://localhost/ >/dev/null 2>&1 || wget -q -O- http://localhost/ >/dev/null 2>&1"; then
                            echo "Container responded after ${i}s"
                            EXIT=0
                            break
                        fi
                        sleep 1
                        EXIT=1
                    done
                    docker logs ${TEST_CONTAINER} | tail -n 50
                    docker rm -f ${TEST_CONTAINER}
                    exit ${EXIT:-0}
                '''
            }
        }

        stage('Build Info') {
            steps {
                sh '''
                    echo "=== Build summary ==="
                    echo "Image:  ${IMAGE_NAME}:${IMAGE_TAG}"
                    echo "Latest: ${IMAGE_NAME}:latest"
                    echo "Build:  ${BUILD_NUMBER}"
                    echo "Job:    ${JOB_NAME}"
                    docker image inspect ${IMAGE_NAME}:${IMAGE_TAG} --format "Created: {{.Created}} | Size: {{.Size}} bytes"
                '''
            }
        }
    }

    post {
        always {
            echo '=== Cleaning up ==='
            sh 'docker rm -f ${TEST_CONTAINER} >/dev/null 2>&1 || true'
        }
        success {
            echo "BUILD SUCCESS: ${IMAGE_NAME}:${IMAGE_TAG}"
        }
        failure {
            echo 'BUILD FAILED'
        }
    }
}
